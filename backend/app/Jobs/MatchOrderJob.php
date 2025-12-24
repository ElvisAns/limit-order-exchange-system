<?php

namespace App\Jobs;

use App\Events\OrderbookUpdated;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Match Order Job
 *
 * Attempts to match a newly created order with existing opposite orders.
 *
 * Matching Rules (Full Match Only):
 * - New BUY → match with first SELL where sell.price <= buy.price
 * - New SELL → match with first BUY where buy.price >= sell.price
 * - Only full matches (no partial fills)
 */
class MatchOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Lock the order for update to prevent race conditions
            $order = Order::query()
                ->where('id', $this->orderId)
                ->where('status', Order::STATUS_OPEN)
                ->lockForUpdate()
                ->first();

            // Order not found or already processed
            if (! $order) {
                Log::info("Order {$this->orderId} not found or already processed");

                return;
            }

            // Try to find a matching order
            $matchingOrder = $this->findMatchingOrder($order);

            if (! $matchingOrder) {
                Log::info("No matching order found for Order #{$order->id}");

                return;
            }

            // Execute the match
            $this->executeMatch($order, $matchingOrder);

            Log::info("Successfully matched Order #{$order->id} with Order #{$matchingOrder->id}");
        });
    }

    /**
     * Find a matching order.
     */
    protected function findMatchingOrder(Order $order): ?Order
    {
        if ($order->side === Order::SIDE_BUY) {
            // New BUY → match with first SELL where sell.price <= buy.price
            return Order::query()
                ->where('symbol', $order->symbol)
                ->where('side', Order::SIDE_SELL)
                ->where('status', Order::STATUS_OPEN)
                ->where('user_id', '!=', $order->user_id) // Prevent self-trading
                ->where('amount', $order->amount) // Full match only
                ->where('price', '<=', $order->price)
                ->orderBy('price', 'asc') // Best price first
                ->orderBy('created_at', 'asc') // FIFO
                ->lockForUpdate()
                ->first();
        } else {
            // New SELL → match with first BUY where buy.price >= sell.price
            return Order::query()
                ->where('symbol', $order->symbol)
                ->where('side', Order::SIDE_BUY)
                ->where('status', Order::STATUS_OPEN)
                ->where('user_id', '!=', $order->user_id) // Prevent self-trading
                ->where('amount', $order->amount) // Full match only
                ->where('price', '>=', $order->price)
                ->orderBy('price', 'desc') // Best price first
                ->orderBy('created_at', 'asc') // FIFO
                ->lockForUpdate()
                ->first();
        }
    }

    /**
     * Execute the match between two orders.
     */
    protected function executeMatch(Order $newOrder, Order $matchingOrder): void
    {
        // Determine buyer and seller
        if ($newOrder->side === Order::SIDE_BUY) {
            $buyOrder = $newOrder;
            $sellOrder = $matchingOrder;
        } else {
            $buyOrder = $matchingOrder;
            $sellOrder = $newOrder;
        }

        $buyer = $buyOrder->user;
        $seller = $sellOrder->user;

        // The matched price is the existing order's price (price-time priority)
        $matchedPrice = $matchingOrder->price;
        $amount = $newOrder->amount; // Same amount (full match only)

        // Calculate commission on matched price
        $matchedCommission = bcmul($matchedPrice, '0.015', 8);

        // Handle buyer's payment and potential refund
        $buyerOriginalPrice = $buyOrder->price;
        $buyerOriginalCommission = bcmul($buyerOriginalPrice, '0.015', 8);
        $buyerOriginalTotal = bcadd($buyerOriginalPrice, $buyerOriginalCommission, 8);

        $matchedTotal = bcadd($matchedPrice, $matchedCommission, 8);

        // If buyer offered more than matched price, refund the difference
        if (bccomp($buyerOriginalTotal, $matchedTotal, 8) > 0) {
            $refundAmount = bcsub($buyerOriginalTotal, $matchedTotal, 8);
            $buyer->increment('balance', $refundAmount);

            Log::info("Refunded buyer {$buyer->id}: {$refundAmount} USD (offered {$buyerOriginalPrice}, matched at {$matchedPrice})");
        }

        // Seller receives matched price
        $seller->increment('balance', $matchedPrice);

        // Calculate seller commission based on amount
        $sellerCommission = bcmul($amount, '0.015', 8);

        // Transfer crypto from seller to buyer
        // Find or create buyer's asset
        $buyerAsset = Asset::firstOrCreate(
            [
                'user_id' => $buyer->id,
                'symbol' => $newOrder->symbol,
            ],
            [
                'amount' => 0,
                'locked_amount' => 0,
            ]
        );

        // Buyer receives the amount
        $buyerAsset->increment('amount', $amount);

        // Seller's locked amount is freed (commission deducted)
        $sellerAsset = Asset::query()
            ->where('user_id', $seller->id)
            ->where('symbol', $newOrder->symbol)
            ->lockForUpdate()
            ->first();

        // Remove from locked (amount + commission was locked)
        $totalSellerLocked = bcadd($amount, $sellerCommission, 8);
        $sellerAsset->decrement('locked_amount', $totalSellerLocked);

        // Mark both orders as filled
        $buyOrder->update(['status' => Order::STATUS_FILLED]);
        $sellOrder->update(['status' => Order::STATUS_FILLED]);

        // Create trade record
        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $newOrder->symbol,
            'price' => $matchedPrice,
            'amount' => $amount,
        ]);

        // Broadcast to buyer
        $this->broadcastToUser($buyer, 'buy', $buyOrder->id, $newOrder->symbol, $amount, $matchedPrice, $refundAmount ?? null);

        // Broadcast to seller
        $this->broadcastToUser($seller, 'sell', $sellOrder->id, $newOrder->symbol, $amount, $matchedPrice);

        // Broadcast orderbook update (orders were matched/filled)
        OrderbookUpdated::dispatch($newOrder->symbol, 'matched');
    }

    /**
     * Broadcast order matched event to user.
     */
    protected function broadcastToUser($user, string $side, int $orderId, string $symbol, string $amount, string $matchedPrice, ?string $refundAmount = null): void
    {
        // Refresh user to get latest balance
        $user->refresh();

        // Get user's current assets
        $assets = Asset::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($asset) {
                return [
                    'symbol' => $asset->symbol,
                    'amount' => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ];
            })
            ->toArray();

        // Dispatch the broadcast event
        OrderMatched::dispatch(
            $user->id,
            $side,
            $orderId,
            $symbol,
            $amount,
            $matchedPrice,
            (string) $user->balance,
            $assets,
            $refundAmount
        );
    }
}

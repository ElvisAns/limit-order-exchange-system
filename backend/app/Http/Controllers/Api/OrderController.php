<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Asset;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Order Controller
 *
 * Handles order creation, cancellation, and orderbook retrieval.
 *
 * Order Model:
 * - price = TOTAL USD amount (not per-unit price)
 * - amount = Quantity of crypto asset
 * - Example: Selling 0.1 BTC for $10,000 total → price=10000, amount=0.1
 *
 * Commission System:
 * - All orders include a 1.5% commission fee
 * - Buy orders: Fee paid in USD (buyer pays price × 1.015)
 * - Sell orders: Fee paid in asset (seller locks amount × 1.015)
 * - Fees are locked when order is created
 * - Fees are returned when order is cancelled
 * - Example: 0.1 BTC for $10,000 → Commission = $150 USD (for buyer)
 */
class OrderController extends Controller
{
    /**
     * Commission rate (1.5%)
     */
    public const COMMISSION_RATE = 0.015;

    /**
     * Get all open orders for the orderbook.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => ['required', 'string', 'max:10'],
        ]);

        $symbol = $request->query('symbol');

        $buyOrders = Order::query()
            ->where('symbol', $symbol)
            ->where('side', Order::SIDE_BUY)
            ->where('status', Order::STATUS_OPEN)
            ->orderBy('created_at', 'desc')
            ->orderBy('amount', 'desc')
            ->orderBy('price', 'asc')
            ->get();

        $sellOrders = Order::query()
            ->where('symbol', $symbol)
            ->where('side', Order::SIDE_SELL)
            ->where('status', Order::STATUS_OPEN)
            ->orderBy('created_at', 'desc')
            ->orderBy('amount', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'symbol' => $symbol,
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders,
        ]);
    }

    /**
     * Create a new limit order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        return DB::transaction(function () use ($validated, $user) {
            if ($validated['side'] === Order::SIDE_BUY) {
                return $this->createBuyOrder($user, $validated);
            } else {
                return $this->createSellOrder($user, $validated);
            }
        });
    }

    /**
     * Create a buy order.
     */
    protected function createBuyOrder($user, array $validated): JsonResponse
    {
        // Price is the TOTAL USD amount (not per-unit)
        $priceUSD = $validated['price'];

        // Calculate commission (1.5% of USD price)
        $commission = bcmul($priceUSD, (string) self::COMMISSION_RATE, 8);

        // Total cost including commission
        $totalCost = bcadd($priceUSD, $commission, 8);

        // Check if user has enough balance (including commission)
        if (bccomp($user->balance, $totalCost, 8) < 0) {
            return response()->json([
                'message' => 'Insufficient balance. Amount required includes 1.5% commission fee.',
                'price' => $priceUSD,
                'commission' => $commission,
                'total_required' => $totalCost,
                'available' => $user->balance,
            ], 400);
        }

        // Deduct total cost (including commission) from user balance
        $user->decrement('balance', $totalCost);

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => $validated['symbol'],
            'side' => Order::SIDE_BUY,
            'price' => $validated['price'],
            'amount' => $validated['amount'],
            'status' => Order::STATUS_OPEN,
        ]);

        return response()->json([
            'message' => 'Buy order created successfully.',
            'order' => $order->fresh(),
            'locked_balance' => $totalCost,
            'breakdown' => [
                'price' => $priceUSD,
                'commission' => $commission,
                'total_deducted' => $totalCost,
                'commission_rate' => '1.5%',
            ],
        ]);
    }

    /**
     * Create a sell order.
     */
    protected function createSellOrder($user, array $validated): JsonResponse
    {
        // Find or fail the asset
        $asset = Asset::query()
            ->where('user_id', $user->id)
            ->where('symbol', $validated['symbol'])
            ->first();

        if (! $asset) {
            return response()->json([
                'message' => 'You do not own this asset.',
                'symbol' => $validated['symbol'],
            ], 400);
        }

        // Calculate commission in asset (1.5% of amount)
        $commission = bcmul($validated['amount'], (string) self::COMMISSION_RATE, 8);

        // Total amount to lock (including commission)
        $totalAmount = bcadd($validated['amount'], $commission, 8);

        // Check if user has enough available amount (including commission)
        if (bccomp($asset->amount, $totalAmount, 8) < 0) {
            return response()->json([
                'message' => 'Insufficient asset amount. Amount required includes 1.5% commission fee.',
                'base_amount' => $validated['amount'],
                'commission' => $commission,
                'total_required' => $totalAmount,
                'available' => $asset->amount,
            ], 400);
        }

        // Move total amount (including commission) to locked_amount
        $asset->decrement('amount', $totalAmount);
        $asset->increment('locked_amount', $totalAmount);

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => $validated['symbol'],
            'side' => Order::SIDE_SELL,
            'price' => $validated['price'],
            'amount' => $validated['amount'],
            'status' => Order::STATUS_OPEN,
        ]);

        return response()->json([
            'message' => 'Sell order created successfully.',
            'order' => $order->fresh(),
            'locked_amount' => $totalAmount,
            'breakdown' => [
                'base_amount' => $validated['amount'],
                'commission' => $commission,
                'commission_rate' => '1.5%',
            ],
        ]);
    }

    /**
     * Cancel an existing order.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $order = Order::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($order->status !== Order::STATUS_OPEN) {
            return response()->json([
                'message' => 'Only open orders can be cancelled.',
            ], 400);
        }

        return DB::transaction(function () use ($order, $user) {
            if ($order->side === Order::SIDE_BUY) {
                // Price is the total USD amount
                $priceUSD = $order->price;

                // Calculate commission
                $commission = bcmul($priceUSD, (string) self::COMMISSION_RATE, 8);

                // Total locked amount (including commission)
                $totalLocked = bcadd($priceUSD, $commission, 8);

                // Return locked USD (including commission) back to user balance
                $user->increment('balance', $totalLocked);

                $unlocked = [
                    'usd_returned' => $totalLocked,
                    'price' => $priceUSD,
                    'commission_returned' => $commission,
                ];
            } else {
                // Calculate commission in asset
                $commission = bcmul($order->amount, (string) self::COMMISSION_RATE, 8);

                // Total locked amount (including commission)
                $totalLocked = bcadd($order->amount, $commission, 8);

                // Return locked asset amount (including commission) back to available
                $asset = Asset::query()
                    ->where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->firstOrFail();

                $asset->decrement('locked_amount', $totalLocked);
                $asset->increment('amount', $totalLocked);

                $unlocked = [
                    'asset_returned' => $totalLocked,
                    'base_amount' => $order->amount,
                    'commission_returned' => $commission,
                ];
            }

            // Update order status
            $order->update([
                'status' => Order::STATUS_CANCELLED,
            ]);

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'order' => $order->fresh(),
                'unlocked' => $unlocked,
            ]);
        });
    }
}

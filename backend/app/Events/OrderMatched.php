<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Order Matched Event
 *
 * Broadcasts to a user's private channel when their order is matched.
 * Includes updated balance and asset information.
 */
class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public string $orderSide,
        public int $orderId,
        public string $symbol,
        public string $amount,
        public string $matchedPrice,
        public string $newBalance,
        public array $assets,
        public ?string $refundAmount = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'side' => $this->orderSide,
            'symbol' => $this->symbol,
            'amount' => $this->amount,
            'matched_price' => $this->matchedPrice,
            'new_balance' => $this->newBalance,
            'assets' => $this->assets,
            'refund_amount' => $this->refundAmount,
            'matched_at' => now()->toIso8601String(),
        ];
    }
}

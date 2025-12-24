<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'];
        $buyer = \App\Models\User::factory()->create();
        $seller = \App\Models\User::factory()->create();

        return [
            'buy_order_id' => \App\Models\Order::factory()->buy()->filled()->create(['user_id' => $buyer->id]),
            'sell_order_id' => \App\Models\Order::factory()->sell()->filled()->create(['user_id' => $seller->id]),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => fake()->randomElement($symbols),
            'price' => fake()->randomFloat(8, 1, 100000),
            'amount' => fake()->randomFloat(8, 0.001, 100),
        ];
    }

    /**
     * Create a trade for a specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => $symbol,
        ]);
    }
}

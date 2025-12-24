<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'];
        $sides = ['buy', 'sell'];

        return [
            'user_id' => \App\Models\User::factory(),
            'symbol' => fake()->randomElement($symbols),
            'side' => fake()->randomElement($sides),
            'price' => fake()->randomFloat(8, 1, 100000),
            'amount' => fake()->randomFloat(8, 0.001, 100),
            'status' => \App\Models\Order::STATUS_OPEN,
        ];
    }

    /**
     * Create a buy order.
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => \App\Models\Order::SIDE_BUY,
        ]);
    }

    /**
     * Create a sell order.
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => \App\Models\Order::SIDE_SELL,
        ]);
    }

    /**
     * Create a filled order.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_FILLED,
        ]);
    }

    /**
     * Create a cancelled order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create an order for a specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => $symbol,
        ]);
    }
}

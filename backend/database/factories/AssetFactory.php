<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'];

        return [
            'user_id' => \App\Models\User::factory(),
            'symbol' => fake()->randomElement($symbols),
            'amount' => fake()->randomFloat(8, 0, 1000),
            'locked_amount' => fake()->randomFloat(8, 0, 10),
        ];
    }

    /**
     * Create an asset with specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => $symbol,
        ]);
    }

    /**
     * Create an asset with no locked amount.
     */
    public function unlocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_amount' => 0,
        ]);
    }
}

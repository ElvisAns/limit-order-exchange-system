<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'];

        // Create 50 open orders (mix of buy and sell)
        foreach (range(1, 50) as $i) {
            \App\Models\Order::factory()->create([
                'user_id' => $users->random()->id,
                'symbol' => fake()->randomElement($symbols),
                'status' => \App\Models\Order::STATUS_OPEN,
            ]);
        }

        // Create 30 filled orders
        foreach (range(1, 30) as $i) {
            \App\Models\Order::factory()
                ->filled()
                ->create([
                    'user_id' => $users->random()->id,
                    'symbol' => fake()->randomElement($symbols),
                ]);
        }

        // Create 20 cancelled orders
        foreach (range(1, 20) as $i) {
            \App\Models\Order::factory()
                ->cancelled()
                ->create([
                    'user_id' => $users->random()->id,
                    'symbol' => fake()->randomElement($symbols),
                ]);
        }
    }
}

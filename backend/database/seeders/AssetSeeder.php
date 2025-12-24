<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'];

        // Give each user 2-5 random crypto assets
        foreach ($users as $user) {
            $numberOfAssets = rand(2, 5);
            $selectedSymbols = fake()->randomElements($symbols, $numberOfAssets);

            foreach ($selectedSymbols as $symbol) {
                \App\Models\Asset::factory()->create([
                    'user_id' => $user->id,
                    'symbol' => $symbol,
                    'amount' => fake()->randomFloat(8, 0.1, 100),
                    'locked_amount' => fake()->randomFloat(8, 0, 5),
                ]);
            }
        }
    }
}

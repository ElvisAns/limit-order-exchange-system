<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user with known credentials
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'balance' => 50000.00,
        ]);

        // Create 20 random users with random USD balances
        \App\Models\User::factory()
            ->count(20)
            ->create();

        // Create some users with specific balance ranges
        \App\Models\User::factory()
            ->count(5)
            ->withBalance(10000.00)
            ->create();

        \App\Models\User::factory()
            ->count(5)
            ->withBalance(1000.00)
            ->create();
    }
}

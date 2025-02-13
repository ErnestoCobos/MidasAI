<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin users
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@midas.trade',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Ernesto Cobos',
            'email' => 'ernesto@cobos.io',
            'password' => bcrypt('Aa121292#1221#'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Seed trading pairs and market data
        $this->call([
            TradingPairSeeder::class,
            MarketDataSeeder::class,
        ]);
    }
}

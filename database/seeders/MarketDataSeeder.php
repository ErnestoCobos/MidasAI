<?php

namespace Database\Seeders;

use App\Models\MarketData;
use App\Models\TradingPair;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MarketDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pairs = TradingPair::all();
        $now = Carbon::now();

        foreach ($pairs as $pair) {
            // Generate data for the last 7 days
            for ($i = 0; $i < 7; $i++) {
                $timestamp = $now->copy()->subDays($i);
                $basePrice = 100 + rand(-20, 20); // Random base price around 100

                // Generate 24 hourly data points for each day
                for ($hour = 0; $hour < 24; $hour++) {
                    $hourlyTimestamp = $timestamp->copy()->addHours($hour);
                    $volatility = rand(1, 10) / 100; // 1-10% volatility
                    $buyRatio = rand(30, 70) / 100; // 0.3-0.7 buy/sell ratio

                    MarketData::create([
                        'trading_pair_id' => $pair->id,
                        'timestamp' => $hourlyTimestamp,
                        'open' => $basePrice * (1 + rand(-5, 5) / 100),
                        'high' => $basePrice * (1 + rand(0, 10) / 100),
                        'low' => $basePrice * (1 - rand(0, 10) / 100),
                        'close' => $basePrice * (1 + rand(-5, 5) / 100),
                        'volume' => rand(1000, 10000),
                        'quote_volume' => rand(100000, 1000000),
                        'number_of_trades' => rand(100, 1000),
                        'taker_buy_volume' => rand(500, 5000),
                        'taker_buy_quote_volume' => rand(50000, 500000),
                        'daily_volatility' => $volatility,
                        'buy_sell_ratio' => $buyRatio,
                    ]);
                }
            }
        }
    }
}

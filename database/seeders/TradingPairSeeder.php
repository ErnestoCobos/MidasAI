<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use Illuminate\Database\Seeder;

class TradingPairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pairs = [
            [
                'symbol' => 'BTC/USDT',
                'base_asset' => 'BTC',
                'quote_asset' => 'USDT',
                'min_qty' => 0.00001,
                'max_qty' => 1000,
                'min_notional' => 10,
                'max_position_size' => 10,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'ETH/USDT',
                'base_asset' => 'ETH',
                'quote_asset' => 'USDT',
                'min_qty' => 0.0001,
                'max_qty' => 10000,
                'min_notional' => 10,
                'max_position_size' => 100,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
        ];

        foreach ($pairs as $pair) {
            TradingPair::updateOrCreate(
                ['symbol' => $pair['symbol']],
                $pair
            );
        }
    }
}

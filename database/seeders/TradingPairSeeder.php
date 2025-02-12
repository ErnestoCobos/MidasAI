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
        $tradingPairs = [
            [
                'symbol' => 'BTC/USDT',
                'base_asset' => 'BTC',
                'quote_asset' => 'USDT',
                'min_qty' => 0.00001,
                'max_qty' => 100,
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
                'max_qty' => 1000,
                'min_notional' => 10,
                'max_position_size' => 100,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'BNB/USDT',
                'base_asset' => 'BNB',
                'quote_asset' => 'USDT',
                'min_qty' => 0.001,
                'max_qty' => 10000,
                'min_notional' => 10,
                'max_position_size' => 1000,
                'maker_fee' => 0.075,
                'taker_fee' => 0.075,
                'is_active' => true,
            ],
            [
                'symbol' => 'SOL/USDT',
                'base_asset' => 'SOL',
                'quote_asset' => 'USDT',
                'min_qty' => 0.01,
                'max_qty' => 10000,
                'min_notional' => 10,
                'max_position_size' => 5000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'ADA/USDT',
                'base_asset' => 'ADA',
                'quote_asset' => 'USDT',
                'min_qty' => 1,
                'max_qty' => 100000,
                'min_notional' => 10,
                'max_position_size' => 50000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'DOT/USDT',
                'base_asset' => 'DOT',
                'quote_asset' => 'USDT',
                'min_qty' => 0.1,
                'max_qty' => 10000,
                'min_notional' => 10,
                'max_position_size' => 5000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'XRP/USDT',
                'base_asset' => 'XRP',
                'quote_asset' => 'USDT',
                'min_qty' => 1,
                'max_qty' => 100000,
                'min_notional' => 10,
                'max_position_size' => 50000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'DOGE/USDT',
                'base_asset' => 'DOGE',
                'quote_asset' => 'USDT',
                'min_qty' => 10,
                'max_qty' => 1000000,
                'min_notional' => 10,
                'max_position_size' => 500000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'MATIC/USDT',
                'base_asset' => 'MATIC',
                'quote_asset' => 'USDT',
                'min_qty' => 1,
                'max_qty' => 100000,
                'min_notional' => 10,
                'max_position_size' => 50000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
            [
                'symbol' => 'LINK/USDT',
                'base_asset' => 'LINK',
                'quote_asset' => 'USDT',
                'min_qty' => 0.1,
                'max_qty' => 10000,
                'min_notional' => 10,
                'max_position_size' => 5000,
                'maker_fee' => 0.1,
                'taker_fee' => 0.1,
                'is_active' => true,
            ],
        ];

        foreach ($tradingPairs as $pair) {
            TradingPair::create($pair);
        }
    }
}

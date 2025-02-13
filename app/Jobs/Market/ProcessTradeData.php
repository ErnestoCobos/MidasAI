<?php

namespace App\Jobs\Market;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;

class ProcessTradeData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('market-data');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $symbol = $this->data['s'];
            
            // Update recent trades cache
            $cacheKey = "binance_trades_{$symbol}";
            $recentTrades = Cache::get($cacheKey, []);
            
            // Add new trade to the beginning
            array_unshift($recentTrades, [
                'id' => $this->data['t'],
                'price' => $this->data['p'],
                'quantity' => $this->data['q'],
                'time' => $this->data['T'],
                'is_buyer_maker' => $this->data['m'],
                'is_best_match' => $this->data['M']
            ]);
            
            // Keep only last 100 trades
            $recentTrades = array_slice($recentTrades, 0, 100);
            
            // Update cache
            Cache::put($cacheKey, $recentTrades, now()->addMinutes(5));

            // Update trade statistics
            $this->updateTradeStats($symbol, $this->data);

            // Log success (but only for significant trades to avoid log spam)
            if (floatval($this->data['q']) * floatval($this->data['p']) > 10000) { // Only log trades > 10000 USDT
                SystemLog::create([
                    'level' => 'INFO',
                    'component' => 'ProcessTradeData',
                    'event' => 'SIGNIFICANT_TRADE_PROCESSED',
                    'message' => "Processed significant trade for {$symbol}",
                    'context' => [
                        'symbol' => $symbol,
                        'price' => $this->data['p'],
                        'quantity' => $this->data['q'],
                        'value' => floatval($this->data['q']) * floatval($this->data['p'])
                    ]
                ]);
            }

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'ProcessTradeData',
                'event' => 'TRADE_PROCESSING_ERROR',
                'message' => $e->getMessage(),
                'context' => [
                    'data' => $this->data,
                    'error' => $e->getMessage()
                ]
            ]);

            throw $e;
        }
    }

    /**
     * Update trade statistics in cache
     */
    protected function updateTradeStats(string $symbol, array $trade): void
    {
        $statsKey = "binance_trade_stats_{$symbol}";
        $stats = Cache::get($statsKey, [
            'buy_volume' => 0,
            'sell_volume' => 0,
            'trades_count' => 0,
            'last_price' => 0,
            'high_price' => 0,
            'low_price' => PHP_FLOAT_MAX,
            'volume' => 0
        ]);

        $quantity = floatval($trade['q']);
        $price = floatval($trade['p']);

        // Update stats
        if ($trade['m']) { // Buyer is maker (sell trade)
            $stats['sell_volume'] += $quantity;
        } else { // Seller is maker (buy trade)
            $stats['buy_volume'] += $quantity;
        }

        $stats['trades_count']++;
        $stats['last_price'] = $price;
        $stats['high_price'] = max($stats['high_price'], $price);
        $stats['low_price'] = min($stats['low_price'], $price);
        $stats['volume'] += $quantity;

        // Store for 5 minutes
        Cache::put($statsKey, $stats, now()->addMinutes(5));
    }
}

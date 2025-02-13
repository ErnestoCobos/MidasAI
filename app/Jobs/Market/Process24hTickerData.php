<?php

namespace App\Jobs\Market;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;

class Process24hTickerData implements ShouldQueue
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
            
            // Update cache with 24h ticker data
            $cacheKey = "binance_24hr_{$symbol}";
            Cache::put($cacheKey, [
                'price_change' => $this->data['p'],
                'price_change_percent' => $this->data['P'],
                'weighted_avg_price' => $this->data['w'],
                'last_price' => $this->data['c'],
                'last_qty' => $this->data['Q'],
                'best_bid' => $this->data['b'],
                'best_bid_qty' => $this->data['B'],
                'best_ask' => $this->data['a'],
                'best_ask_qty' => $this->data['A'],
                'high' => $this->data['h'],
                'low' => $this->data['l'],
                'volume' => $this->data['v'],
                'quote_volume' => $this->data['q'],
                'open_time' => $this->data['O'],
                'close_time' => $this->data['C'],
                'first_trade_id' => $this->data['F'],
                'last_trade_id' => $this->data['L'],
                'trades_count' => $this->data['n']
            ], now()->addMinutes(5));

            // Log success
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'Process24hTickerData',
                'event' => 'TICKER_DATA_PROCESSED',
                'message' => "Successfully processed 24h ticker data for {$symbol}",
                'context' => [
                    'symbol' => $symbol,
                    'price_change_percent' => $this->data['P'],
                    'last_price' => $this->data['c']
                ]
            ]);

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'Process24hTickerData',
                'event' => 'TICKER_PROCESSING_ERROR',
                'message' => $e->getMessage(),
                'context' => [
                    'data' => $this->data,
                    'error' => $e->getMessage()
                ]
            ]);

            throw $e;
        }
    }
}

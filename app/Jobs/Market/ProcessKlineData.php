<?php

namespace App\Jobs\Market;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MarketData;
use App\Models\TradingPair;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;

class ProcessKlineData implements ShouldQueue
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
            $kline = $this->data['k'];

            // Get trading pair
            $formattedSymbol = substr($symbol, 0, -4) . '/' . substr($symbol, -4);
            $tradingPair = TradingPair::where('symbol', $formattedSymbol)->first();

            if (!$tradingPair) {
                throw new \Exception("Trading pair not found: {$formattedSymbol}");
            }

            // Calculate additional metrics
            $buyVolume = $kline['V']; // Taker buy volume
            $totalVolume = $kline['v']; // Total volume
            $sellVolume = $totalVolume - $buyVolume;
            $buySellRatio = $sellVolume > 0 ? $buyVolume / $sellVolume : 0;

            // Calculate volatility (using high-low range)
            $volatility = ($kline['h'] - $kline['l']) / $kline['o'] * 100;

            // Create or update market data
            MarketData::create([
                'trading_pair_id' => $tradingPair->id,
                'timestamp' => $kline['t'],
                'open' => $kline['o'],
                'high' => $kline['h'],
                'low' => $kline['l'],
                'close' => $kline['c'],
                'volume' => $kline['v'],
                'quote_volume' => $kline['q'],
                'number_of_trades' => $kline['n'],
                'taker_buy_volume' => $kline['V'],
                'taker_buy_quote_volume' => $kline['Q'],
                'daily_volatility' => $volatility,
                'buy_sell_ratio' => $buySellRatio
            ]);

            // Update cache
            $this->updateCache($symbol, $kline);

            // Log success
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'ProcessKlineData',
                'event' => 'KLINE_DATA_PROCESSED',
                'message' => "Successfully processed kline data for {$symbol}",
                'context' => [
                    'symbol' => $symbol,
                    'interval' => $kline['i'],
                    'close_price' => $kline['c']
                ]
            ]);

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'ProcessKlineData',
                'event' => 'KLINE_PROCESSING_ERROR',
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
     * Update cache with latest data
     */
    protected function updateCache(string $symbol, array $kline): void
    {
        $cacheKey = "binance_price_{$symbol}";
        Cache::put($cacheKey, $kline['c'], now()->addMinutes(5));

        $candleKey = "binance_candle_{$symbol}";
        Cache::put($candleKey, [
            'open' => $kline['o'],
            'high' => $kline['h'],
            'low' => $kline['l'],
            'close' => $kline['c'],
            'volume' => $kline['v'],
            'timestamp' => $kline['t']
        ], now()->addMinutes(5));
    }
}

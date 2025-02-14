<?php

namespace App\Services\Binance;

use App\Models\MarketData;
use App\Models\SystemLog;
use App\Models\TradingPair;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BinanceWebSocket
{
    protected ?string $apiKey = null;
    protected ?string $apiSecret = null;
    protected string $baseUrl;
    protected bool $testnet;
    protected array $activeStreams = [];
    protected array $callbacks = [];
    protected bool $running = true;

    public function __construct()
    {
        $this->testnet = config('services.binance.testnet', true);
        $this->apiKey = config('services.binance.key', '');
        $this->apiSecret = config('services.binance.secret', '');
        $this->baseUrl = $this->testnet
            ? 'https://testnet.binance.vision/api/v3'
            : 'https://api.binance.com/api/v3';

        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new \Exception('Binance API credentials not configured. Please set BINANCE_API_KEY and BINANCE_API_SECRET in your .env file.');
        }
    }

    /**
     * Connect and start streaming data
     */
    public function connect(array $streams, callable $callback = null)
    {
        $this->activeStreams = $streams;
        
        if ($callback) {
            $this->callbacks[] = $callback;
        }

        while ($this->running) {
            try {
                $pairs = TradingPair::where('is_active', true)->get();
                
                foreach ($pairs as $pair) {
                    $symbol = str_replace('/', '', $pair->symbol);
                    
                    // Get 24hr ticker data
                    $response = Http::get($this->baseUrl . '/ticker/24hr', [
                        'symbol' => $symbol
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        
                        // Get klines data
                        $klines = Http::get($this->baseUrl . '/klines', [
                            'symbol' => $symbol,
                            'interval' => '1m',
                            'limit' => 1
                        ])->json();

                        if (!empty($klines)) {
                            $kline = $klines[0];
                            $this->processData($data, $kline, $pair);
                        }
                    }
                }

                // Sleep for 1 second to avoid rate limits
                sleep(1);

            } catch (\Exception $e) {
                SystemLog::create([
                    'level' => 'ERROR',
                    'component' => 'BinanceWebSocket',
                    'event' => 'CONNECTION_ERROR',
                    'message' => $e->getMessage()
                ]);

                // Sleep for 5 seconds before retrying
                sleep(5);
            }
        }
    }

    /**
     * Process market data
     */
    protected function processData($ticker, $kline, TradingPair $pair)
    {
        try {
            // Create market data record
            MarketData::create([
                'trading_pair_id' => $pair->id,
                'timestamp' => now(),
                'open' => $kline[1],  // Open price
                'high' => $kline[2],  // High price
                'low' => $kline[3],   // Low price
                'close' => $kline[4], // Close price
                'volume' => $kline[5], // Volume
                'quote_volume' => $kline[7], // Quote volume
                'number_of_trades' => $kline[8], // Number of trades
                'taker_buy_volume' => $kline[9], // Taker buy volume
                'taker_buy_quote_volume' => $kline[10], // Taker buy quote volume
                'daily_volatility' => $this->calculateVolatility($ticker),
                'buy_sell_ratio' => $this->calculateBuySellRatio($ticker),
            ]);

            // Update cache
            $this->updateCache($pair->symbol, $ticker, $kline);

            // Execute callbacks
            foreach ($this->callbacks as $callback) {
                $callback([
                    'ticker' => $ticker,
                    'kline' => $kline
                ]);
            }

        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'BinanceWebSocket',
                'event' => 'DATA_PROCESSING_ERROR',
                'message' => $e->getMessage(),
                'context' => [
                    'ticker' => $ticker,
                    'kline' => $kline,
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Calculate volatility
     */
    protected function calculateVolatility($data)
    {
        $high = floatval($data['highPrice']);
        $low = floatval($data['lowPrice']);
        $open = floatval($data['openPrice']);

        return (($high - $low) / $open) * 100;
    }

    /**
     * Calculate buy/sell ratio
     */
    protected function calculateBuySellRatio($data)
    {
        $buyVolume = floatval($data['takerBuyVolume'] ?? 0);
        $totalVolume = floatval($data['volume']);
        $sellVolume = $totalVolume - $buyVolume;

        return $sellVolume > 0 ? $buyVolume / $sellVolume : 0;
    }

    /**
     * Update cache with latest data
     */
    protected function updateCache(string $symbol, array $ticker, array $kline): void
    {
        $cacheKey = "binance_price_{$symbol}";
        Cache::put($cacheKey, $ticker['lastPrice'], now()->addMinutes(5));

        $candleKey = "binance_candle_{$symbol}";
        Cache::put($candleKey, [
            'open' => $kline[1],
            'high' => $kline[2],
            'low' => $kline[3],
            'close' => $kline[4],
            'volume' => $kline[5],
            'timestamp' => $kline[0]
        ], now()->addMinutes(5));
    }

    /**
     * Close connection
     */
    public function close()
    {
        $this->running = false;
    }

    /**
     * Add callback for message handling
     */
    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }
}

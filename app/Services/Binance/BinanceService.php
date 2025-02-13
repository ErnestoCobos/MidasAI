<?php

namespace App\Services\Binance;

use App\Models\Order;
use App\Models\TradingPair;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Exception;

class BinanceService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;
    protected bool $testnet;
    protected int $recvWindow = 5000;

    public function __construct()
    {
        $this->testnet = config('services.binance.testnet', true);
        $this->apiKey = config('services.binance.key');
        $this->apiSecret = config('services.binance.secret');
        $this->baseUrl = $this->testnet
            ? 'https://testnet.binance.vision/api/v3'
            : 'https://api.binance.com/api/v3';

        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new \Exception('Binance API credentials not configured. Please set BINANCE_API_KEY and BINANCE_API_SECRET in your .env file.');
        }
    }

    /**
     * Get account information
     */
    public function getAccountInfo()
    {
        return $this->sendSignedRequest('GET', '/account');
    }

    /**
     * Get current price for a symbol
     */
    public function getPrice(string $symbol)
    {
        $cacheKey = "binance_price_{$symbol}";
        
        return Cache::remember($cacheKey, 5, function () use ($symbol) {
            $response = $this->sendPublicRequest('GET', '/ticker/price', [
                'symbol' => str_replace('/', '', $symbol)
            ]);
            
            return $response['price'] ?? null;
        });
    }

    /**
     * Get candlestick data
     */
    public function getKlines(string $symbol, string $interval = '1m', int $limit = 500)
    {
        $cacheKey = "binance_klines_{$symbol}_{$interval}_{$limit}";
        
        return Cache::remember($cacheKey, 5, function () use ($symbol, $interval, $limit) {
            return $this->sendPublicRequest('GET', '/klines', [
                'symbol' => str_replace('/', '', $symbol),
                'interval' => $interval,
                'limit' => $limit
            ]);
        });
    }

    /**
     * Place a new order
     */
    public function createOrder(array $params)
    {
        try {
            $response = $this->sendSignedRequest('POST', '/order', $params);
            
            // Log the order
            if (Schema::hasTable('system_logs')) {
                SystemLog::create([
                    'level' => 'INFO',
                    'component' => 'BinanceService',
                    'event' => 'ORDER_CREATED',
                    'message' => "Order created successfully for {$params['symbol']}",
                    'context' => [
                        'params' => $params,
                        'response' => $response
                    ],
                    'logged_at' => now()
                ]);
            }
            
            return $response;
        } catch (Exception $e) {
            if (Schema::hasTable('system_logs')) {
                SystemLog::create([
                    'level' => 'ERROR',
                    'component' => 'BinanceService',
                    'event' => 'ORDER_CREATION_FAILED',
                    'message' => $e->getMessage(),
                    'context' => [
                        'params' => $params,
                        'error' => $e->getMessage()
                    ],
                    'logged_at' => now()
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $symbol, $orderId)
    {
        return $this->sendSignedRequest('DELETE', '/order', [
            'symbol' => str_replace('/', '', $symbol),
            'orderId' => $orderId
        ]);
    }

    /**
     * Get order status
     */
    public function getOrder(string $symbol, $orderId)
    {
        return $this->sendSignedRequest('GET', '/order', [
            'symbol' => str_replace('/', '', $symbol),
            'orderId' => $orderId
        ]);
    }

    /**
     * Get all open orders
     */
    public function getOpenOrders(?string $symbol = null)
    {
        $params = [];
        if ($symbol) {
            $params['symbol'] = str_replace('/', '', $symbol);
        }
        
        return $this->sendSignedRequest('GET', '/openOrders', $params);
    }

    /**
     * Get exchange information
     */
    public function getExchangeInfo()
    {
        return Cache::remember('binance_exchange_info', 3600, function () {
            return $this->sendPublicRequest('GET', '/exchangeInfo');
        });
    }

    /**
     * Send public request to Binance API
     */
    protected function sendPublicRequest(string $method, string $endpoint, array $params = [])
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $response = Http::get($url, $params);
            
            if ($response->failed()) {
                throw new Exception("Binance API error: {$response->body()}");
            }
            
            return $response->json();
        } catch (Exception $e) {
            if (Schema::hasTable('system_logs')) {
                SystemLog::create([
                    'level' => 'ERROR',
                    'component' => 'BinanceService',
                    'event' => 'API_REQUEST_FAILED',
                    'message' => $e->getMessage(),
                    'context' => [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'params' => $params
                    ],
                    'logged_at' => now()
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Send signed request to Binance API
     */
    protected function sendSignedRequest(string $method, string $endpoint, array $params = [])
    {
        $timestamp = now()->timestamp * 1000;
        $params['timestamp'] = $timestamp;
        $params['recvWindow'] = $this->recvWindow;
        
        // Generate signature
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->apiSecret);
        $params['signature'] = $signature;
        
        try {
            $url = $this->baseUrl . $endpoint;
            $response = Http::withHeaders(['X-MBX-APIKEY' => $this->apiKey]);
            
            if ($method === 'GET') {
                $response = $response->get($url, $params);
            } elseif ($method === 'POST') {
                $response = $response->post($url, $params);
            } elseif ($method === 'DELETE') {
                $response = $response->delete($url, $params);
            }
            
            if ($response->failed()) {
                throw new Exception("Binance API error: {$response->body()}");
            }
            
            return $response->json();
        } catch (Exception $e) {
            if (Schema::hasTable('system_logs')) {
                SystemLog::create([
                    'level' => 'ERROR',
                    'component' => 'BinanceService',
                    'event' => 'API_REQUEST_FAILED',
                    'message' => $e->getMessage(),
                    'context' => [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'params' => $params
                    ],
                    'logged_at' => now()
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Format symbol for Binance API
     */
    protected function formatSymbol(string $symbol): string
    {
        return str_replace('/', '', strtoupper($symbol));
    }

    /**
     * Get order book
     */
    public function getOrderBook(string $symbol, int $limit = 100)
    {
        $cacheKey = "binance_orderbook_{$symbol}_{$limit}";
        
        return Cache::remember($cacheKey, 5, function () use ($symbol, $limit) {
            return $this->sendPublicRequest('GET', '/depth', [
                'symbol' => $this->formatSymbol($symbol),
                'limit' => $limit
            ]);
        });
    }

    /**
     * Get recent trades
     */
    public function getRecentTrades(string $symbol, int $limit = 500)
    {
        return $this->sendPublicRequest('GET', '/trades', [
            'symbol' => $this->formatSymbol($symbol),
            'limit' => $limit
        ]);
    }

    /**
     * Get 24hr ticker
     */
    public function get24hrTicker(string $symbol)
    {
        $cacheKey = "binance_24hr_{$symbol}";
        
        return Cache::remember($cacheKey, 5, function () use ($symbol) {
            return $this->sendPublicRequest('GET', '/ticker/24hr', [
                'symbol' => $this->formatSymbol($symbol)
            ]);
        });
    }

    /**
     * Place a market buy order
     */
    public function marketBuy(string $symbol, float $quantity)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'BUY',
            'type' => 'MARKET',
            'quantity' => $quantity
        ]);
    }

    /**
     * Place a market sell order
     */
    public function marketSell(string $symbol, float $quantity)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'SELL',
            'type' => 'MARKET',
            'quantity' => $quantity
        ]);
    }

    /**
     * Place a limit buy order
     */
    public function limitBuy(string $symbol, float $quantity, float $price)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'BUY',
            'type' => 'LIMIT',
            'timeInForce' => 'GTC',
            'quantity' => $quantity,
            'price' => $price
        ]);
    }

    /**
     * Place a limit sell order
     */
    public function limitSell(string $symbol, float $quantity, float $price)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'SELL',
            'type' => 'LIMIT',
            'timeInForce' => 'GTC',
            'quantity' => $quantity,
            'price' => $price
        ]);
    }

    /**
     * Place a stop loss order
     */
    public function stopLoss(string $symbol, float $quantity, float $stopPrice)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'SELL',
            'type' => 'STOP_LOSS',
            'quantity' => $quantity,
            'stopPrice' => $stopPrice
        ]);
    }

    /**
     * Place a take profit order
     */
    public function takeProfit(string $symbol, float $quantity, float $stopPrice)
    {
        return $this->createOrder([
            'symbol' => $this->formatSymbol($symbol),
            'side' => 'SELL',
            'type' => 'TAKE_PROFIT',
            'quantity' => $quantity,
            'stopPrice' => $stopPrice
        ]);
    }
}

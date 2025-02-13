<?php

namespace App\Services\Binance;

use App\Models\MarketData;
use App\Models\SystemLog;
use App\Models\TradingPair;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;
use Exception;

class BinanceWebSocket
{
    protected string $wsBaseUrl;
    protected bool $testnet;
    protected array $activeStreams = [];
    protected ?WebSocket $conn = null;
    protected array $callbacks = [];
    protected bool $reconnecting = false;
    protected int $reconnectAttempts = 0;
    protected const MAX_RECONNECT_ATTEMPTS = 5;

    public function __construct()
    {
        $this->testnet = config('services.binance.testnet', true);
        $this->wsBaseUrl = $this->testnet
            ? 'wss://testnet.binance.vision/ws'
            : 'wss://stream.binance.com:9443/ws';
    }

    /**
     * Connect to WebSocket and subscribe to streams
     */
    public function connect(array $streams, callable $callback = null)
    {
        $this->activeStreams = $streams;
        
        if ($callback) {
            $this->callbacks[] = $callback;
        }

        \Ratchet\Client\connect($this->wsBaseUrl)->then(
            function (WebSocket $conn) {
                $this->conn = $conn;
                $this->reconnecting = false;
                $this->reconnectAttempts = 0;

                SystemLog::create([
                    'level' => 'INFO',
                    'component' => 'BinanceWebSocket',
                    'event' => 'WEBSOCKET_CONNECTED',
                    'message' => 'Successfully connected to Binance WebSocket'
                ]);

                // Subscribe to streams
                $this->subscribe($this->activeStreams);

                // Message handler
                $conn->on('message', function ($msg) {
                    $this->handleMessage($msg);
                });

                // Error handler
                $conn->on('error', function ($error) {
                    $this->handleError($error);
                });

                // Close handler
                $conn->on('close', function ($code = null, $reason = null) {
                    $this->handleClose($code, $reason);
                });
            },
            function (Exception $e) {
                $this->handleConnectionError($e);
            }
        );
    }

    /**
     * Subscribe to streams
     */
    protected function subscribe(array $streams)
    {
        if (!$this->conn) {
            throw new Exception('WebSocket not connected');
        }

        $params = [
            'method' => 'SUBSCRIBE',
            'params' => $streams,
            'id' => time()
        ];

        $this->conn->send(json_encode($params));

        SystemLog::create([
            'level' => 'INFO',
            'component' => 'BinanceWebSocket',
            'event' => 'STREAM_SUBSCRIBED',
            'message' => 'Subscribed to streams: ' . implode(', ', $streams),
            'context' => ['streams' => $streams]
        ]);
    }

    /**
     * Unsubscribe from streams
     */
    protected function unsubscribe(array $streams)
    {
        if (!$this->conn) {
            throw new Exception('WebSocket not connected');
        }

        $params = [
            'method' => 'UNSUBSCRIBE',
            'params' => $streams,
            'id' => time()
        ];

        $this->conn->send(json_encode($params));
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage($msg)
    {
        try {
            $data = json_decode($msg, true);
            
            if (!$data) {
                throw new Exception('Invalid message format');
            }

            // Dispatch job to process the message
            ProcessWebSocketData::dispatch($data)
                ->onQueue('market-data')
                ->delay(now()->addSeconds(1)); // Small delay to prevent overwhelming the queue

            // Execute callbacks if any
            foreach ($this->callbacks as $callback) {
                $callback($data);
            }

            // Update connection status in cache
            Cache::put('binance_websocket_last_message', now(), now()->addMinutes(5));
        } catch (Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'BinanceWebSocket',
                'event' => 'MESSAGE_PROCESSING_ERROR',
                'message' => $e->getMessage(),
                'context' => [
                    'raw_message' => $msg,
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }


    /**
     * Handle WebSocket errors
     */
    protected function handleError(Exception $error)
    {
        SystemLog::create([
            'level' => 'ERROR',
            'component' => 'BinanceWebSocket',
            'event' => 'WEBSOCKET_ERROR',
            'message' => $error->getMessage()
        ]);

        $this->attemptReconnect();
    }

    /**
     * Handle WebSocket close
     */
    protected function handleClose($code = null, $reason = null)
    {
        SystemLog::create([
            'level' => 'WARNING',
            'component' => 'BinanceWebSocket',
            'event' => 'WEBSOCKET_CLOSED',
            'message' => "WebSocket closed: {$code} - {$reason}",
            'context' => [
                'code' => $code,
                'reason' => $reason
            ]
        ]);

        $this->attemptReconnect();
    }

    /**
     * Handle connection errors
     */
    protected function handleConnectionError(Exception $error)
    {
        SystemLog::create([
            'level' => 'ERROR',
            'component' => 'BinanceWebSocket',
            'event' => 'CONNECTION_ERROR',
            'message' => $error->getMessage()
        ]);

        $this->attemptReconnect();
    }

    /**
     * Attempt to reconnect
     */
    protected function attemptReconnect()
    {
        if ($this->reconnecting || $this->reconnectAttempts >= self::MAX_RECONNECT_ATTEMPTS) {
            return;
        }

        $this->reconnecting = true;
        $this->reconnectAttempts++;

        $delay = min(1000 * pow(2, $this->reconnectAttempts), 30000);

        Loop::addTimer($delay / 1000, function () {
            $this->connect($this->activeStreams);
        });
    }

    /**
     * Get trading pair ID from symbol
     */
    protected function getTradingPairId(string $symbol): int
    {
        $formattedSymbol = substr($symbol, 0, -4) . '/' . substr($symbol, -4);
        return TradingPair::where('symbol', $formattedSymbol)->value('id');
    }

    /**
     * Close WebSocket connection
     */
    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    /**
     * Add callback for message handling
     */
    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Subscribe to kline/candlestick stream
     */
    public function subscribeKline(string $symbol, string $interval = '1m')
    {
        $stream = strtolower($symbol) . "@kline_{$interval}";
        $this->activeStreams[] = $stream;
        
        if ($this->conn) {
            $this->subscribe([$stream]);
        }
    }

    /**
     * Subscribe to trade stream
     */
    public function subscribeTrades(string $symbol)
    {
        $stream = strtolower($symbol) . '@trade';
        $this->activeStreams[] = $stream;
        
        if ($this->conn) {
            $this->subscribe([$stream]);
        }
    }

    /**
     * Subscribe to ticker stream
     */
    public function subscribeTicker(string $symbol)
    {
        $stream = strtolower($symbol) . '@ticker';
        $this->activeStreams[] = $stream;
        
        if ($this->conn) {
            $this->subscribe([$stream]);
        }
    }

    /**
     * Subscribe to depth stream
     */
    public function subscribeDepth(string $symbol, string $level = '100ms')
    {
        $stream = strtolower($symbol) . "@depth@{$level}";
        $this->activeStreams[] = $stream;
        
        if ($this->conn) {
            $this->subscribe([$stream]);
        }
    }
}

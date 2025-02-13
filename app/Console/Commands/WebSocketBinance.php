<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use App\Models\SystemLog;
use App\Services\Binance\BinanceWebSocket;
use Illuminate\Console\Command;
use Exception;

class WebSocketBinance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'binance:websocket
                          {--testnet : Use Binance testnet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Binance WebSocket connection';

    protected BinanceWebSocket $webSocket;
    protected bool $running = true;

    /**
     * Create a new command instance.
     */
    public function __construct(BinanceWebSocket $webSocket)
    {
        parent::__construct();
        $this->webSocket = $webSocket;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Setup signal handlers for graceful shutdown
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGTERM, [$this, 'shutdown']);

            $this->info('Starting Binance WebSocket connection...');

            // Get active trading pairs
            $pairs = TradingPair::where('is_active', true)->get();
            
            if ($pairs->isEmpty()) {
                $this->error('No active trading pairs found');
                return 1;
            }

            // Prepare streams for each pair
            $streams = [];
            foreach ($pairs as $pair) {
                $symbol = strtolower(str_replace('/', '', $pair->symbol));
                $streams[] = "{$symbol}@kline_1m";  // 1-minute candlesticks
                $streams[] = "{$symbol}@trade";     // Individual trades
                $streams[] = "{$symbol}@ticker";    // 24hr rolling window ticker
            }

            // Log startup
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'WebSocketBinance',
                'event' => 'WEBSOCKET_STARTING',
                'message' => 'Starting WebSocket connection',
                'context' => [
                    'pairs_count' => $pairs->count(),
                    'streams_count' => count($streams)
                ]
            ]);

            // Connect to WebSocket
            $this->webSocket->connect($streams);

            // Keep the process running
            while ($this->running) {
                // Process signals
                pcntl_signal_dispatch();
                
                // Sleep to avoid CPU overuse
                sleep(1);
            }

            $this->info('WebSocket connection closed');
            return 0;

        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'WebSocketBinance',
                'event' => 'WEBSOCKET_ERROR',
                'message' => $e->getMessage()
            ]);
            
            return 1;
        }
    }

    /**
     * Handle shutdown signals
     */
    public function shutdown()
    {
        $this->info("\nShutting down WebSocket connection...");
        $this->running = false;
        
        // Close WebSocket connection
        $this->webSocket->close();
        
        // Log shutdown
        SystemLog::create([
            'level' => 'INFO',
            'component' => 'WebSocketBinance',
            'event' => 'WEBSOCKET_SHUTDOWN',
            'message' => 'WebSocket connection closed'
        ]);
    }
}

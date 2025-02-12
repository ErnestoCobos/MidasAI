<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use App\Models\TradingStrategy;
use App\Models\Position;
use App\Models\SystemLog;
use App\Services\Trading\StrategyExecutionService;
use App\Services\Binance\BinanceWebSocket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Exception;

class TradingBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading:bot
                          {--strategy= : Strategy name to run}
                          {--pair= : Trading pair to trade}
                          {--testnet : Use Binance testnet}
                          {--monitor : Monitor mode (no trading)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the automated trading bot';

    protected StrategyExecutionService $strategyExecution;
    protected BinanceWebSocket $webSocket;
    protected bool $running = true;

    /**
     * Create a new command instance.
     */
    public function __construct(
        StrategyExecutionService $strategyExecution,
        BinanceWebSocket $webSocket
    ) {
        parent::__construct();
        $this->strategyExecution = $strategyExecution;
        $this->webSocket = $webSocket;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Setup signal handlers
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGTERM, [$this, 'shutdown']);

            // Get trading pairs
            $pairs = $this->getTradingPairs();
            if ($pairs->isEmpty()) {
                $this->error('No trading pairs found');
                return 1;
            }

            // Get strategies
            $strategies = $this->getStrategies();
            if ($strategies->isEmpty()) {
                $this->error('No active strategies found');
                return 1;
            }

            // Display initial status
            $this->displayStatus($pairs, $strategies);

            // Connect to WebSocket for real-time data
            $this->setupWebSocket($pairs);

            // Main trading loop
            while ($this->running) {
                foreach ($strategies as $strategy) {
                    foreach ($pairs as $pair) {
                        try {
                            if (!$this->option('monitor')) {
                                $this->strategyExecution->executeStrategy($strategy, $pair);
                            }
                            $this->displayPairStatus($pair);
                        } catch (Exception $e) {
                            $this->error("Error executing strategy for {$pair->symbol}: {$e->getMessage()}");
                            SystemLog::create([
                                'level' => 'ERROR',
                                'component' => 'TradingBot',
                                'event' => 'STRATEGY_ERROR',
                                'message' => $e->getMessage(),
                                'context' => [
                                    'strategy' => $strategy->name,
                                    'pair' => $pair->symbol
                                ]
                            ]);
                        }
                    }
                }

                // Process signals
                pcntl_signal_dispatch();

                // Sleep to avoid hitting rate limits
                sleep(5);
            }

            $this->info('Bot shutdown completed');
            return 0;

        } catch (Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            SystemLog::create([
                'level' => 'EMERGENCY',
                'component' => 'TradingBot',
                'event' => 'FATAL_ERROR',
                'message' => $e->getMessage()
            ]);
            return 1;
        }
    }

    /**
     * Get trading pairs to monitor
     */
    protected function getTradingPairs()
    {
        $query = TradingPair::where('is_active', true);
        
        if ($pair = $this->option('pair')) {
            $query->where('symbol', $pair);
        }
        
        return $query->get();
    }

    /**
     * Get strategies to execute
     */
    protected function getStrategies()
    {
        $query = TradingStrategy::where('is_active', true);
        
        if ($strategy = $this->option('strategy')) {
            $query->where('name', $strategy);
        }
        
        return $query->get();
    }

    /**
     * Setup WebSocket connection
     */
    protected function setupWebSocket($pairs)
    {
        $streams = [];
        foreach ($pairs as $pair) {
            $symbol = strtolower(str_replace('/', '', $pair->symbol));
            $streams[] = "{$symbol}@kline_1m";  // 1-minute candlesticks
            $streams[] = "{$symbol}@ticker";    // 24hr rolling window ticker
        }

        $this->webSocket->connect($streams, function ($data) {
            $this->processWebSocketData($data);
        });
    }

    /**
     * Process WebSocket data
     */
    protected function processWebSocketData($data)
    {
        if (isset($data['e'])) {
            switch ($data['e']) {
                case 'kline':
                    $this->updatePriceInfo($data);
                    break;
                case 'ticker':
                    $this->updateTickerInfo($data);
                    break;
            }
        }
    }

    /**
     * Update price information from WebSocket
     */
    protected function updatePriceInfo($data)
    {
        $symbol = strtoupper($data['s']);
        $price = $data['k']['c']; // Close price
        
        Cache::put("price_{$symbol}", $price, 60);
    }

    /**
     * Update ticker information from WebSocket
     */
    protected function updateTickerInfo($data)
    {
        $symbol = strtoupper($data['s']);
        
        Cache::put("ticker_{$symbol}", [
            'price' => $data['c'],
            'volume' => $data['v'],
            'change' => $data['p'],
            'change_percent' => $data['P']
        ], 60);
    }

    /**
     * Display initial status
     */
    protected function displayStatus($pairs, $strategies)
    {
        $this->info('Trading Bot Starting...');
        $this->info('Mode: ' . ($this->option('monitor') ? 'Monitor' : 'Trading'));
        $this->info('Environment: ' . ($this->option('testnet') ? 'Testnet' : 'Mainnet'));
        
        $this->newLine();
        $this->info('Active Trading Pairs:');
        foreach ($pairs as $pair) {
            $this->line("- {$pair->symbol}");
        }
        
        $this->newLine();
        $this->info('Active Strategies:');
        foreach ($strategies as $strategy) {
            $this->line("- {$strategy->name}");
        }
        
        $this->newLine();
    }

    /**
     * Display pair status
     */
    protected function displayPairStatus(TradingPair $pair)
    {
        $ticker = Cache::get("ticker_{$pair->symbol}");
        if (!$ticker) return;

        $positions = Position::where('trading_pair_id', $pair->id)
            ->where('status', Position::STATUS_OPEN)
            ->get();

        $this->line(sprintf(
            "\n%s | Price: %.8f | 24h Change: %.2f%% | Volume: %.2f",
            $pair->symbol,
            $ticker['price'],
            $ticker['change_percent'],
            $ticker['volume']
        ));

        if ($positions->isNotEmpty()) {
            foreach ($positions as $position) {
                $pnl = $position->unrealized_pnl;
                $pnlColor = $pnl >= 0 ? 'green' : 'red';
                
                $this->line(sprintf(
                    "Position: %s | Size: %.8f | Entry: %.8f | PnL: <fg=%s>%.2f USDT</>",
                    $position->side,
                    $position->quantity,
                    $position->entry_price,
                    $pnlColor,
                    $pnl
                ));
            }
        }
    }

    /**
     * Shutdown handler
     */
    public function shutdown()
    {
        $this->info("\nShutting down...");
        $this->running = false;
        
        // Close WebSocket connection
        $this->webSocket->close();
        
        // Log shutdown
        SystemLog::create([
            'level' => 'INFO',
            'component' => 'TradingBot',
            'event' => 'SHUTDOWN',
            'message' => 'Bot shutdown initiated'
        ]);
    }
}

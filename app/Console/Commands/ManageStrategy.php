<?php

namespace App\Console\Commands;

use App\Models\TradingStrategy;
use App\Models\Position;
use App\Services\Trading\StrategyExecutionService;
use Illuminate\Console\Command;
use Exception;

class ManageStrategy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strategy:manage
                          {action : Action to perform (list|create|update|delete|enable|disable|metrics)}
                          {--name= : Strategy name}
                          {--timeframe=1m : Trading timeframe}
                          {--max-positions=3 : Maximum concurrent positions}
                          {--max-drawdown=15 : Maximum drawdown percentage}
                          {--profit-target=2 : Profit target percentage}
                          {--stop-loss=1 : Stop loss percentage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage trading strategies';

    protected StrategyExecutionService $strategyExecution;

    /**
     * Create a new command instance.
     */
    public function __construct(StrategyExecutionService $strategyExecution)
    {
        parent::__construct();
        $this->strategyExecution = $strategyExecution;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $action = $this->argument('action');

            switch ($action) {
                case 'list':
                    $this->listStrategies();
                    break;
                case 'create':
                    $this->createStrategy();
                    break;
                case 'update':
                    $this->updateStrategy();
                    break;
                case 'delete':
                    $this->deleteStrategy();
                    break;
                case 'enable':
                    $this->enableStrategy();
                    break;
                case 'disable':
                    $this->disableStrategy();
                    break;
                case 'metrics':
                    $this->showMetrics();
                    break;
                default:
                    $this->error("Unknown action: {$action}");
                    return 1;
            }

            return 0;
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * List all strategies
     */
    protected function listStrategies()
    {
        $strategies = TradingStrategy::all();
        
        if ($strategies->isEmpty()) {
            $this->info('No strategies found');
            return;
        }

        $this->info("\nTrading Strategies");
        $this->line(str_repeat('-', 100));
        $this->line(sprintf("%-20s %-10s %-15s %-15s %-15s %-15s",
            "Name", "Status", "Timeframe", "Max Positions", "Win Rate", "Profit Factor"
        ));
        $this->line(str_repeat('-', 100));

        foreach ($strategies as $strategy) {
            $this->line(sprintf("%-20s %-10s %-15s %-15d %-15.2f %-15.2f",
                $strategy->name,
                $strategy->is_active ? 'Active' : 'Inactive',
                $strategy->timeframe,
                $strategy->max_positions,
                $strategy->win_rate,
                $strategy->profit_factor
            ));
        }
    }

    /**
     * Create new strategy
     */
    protected function createStrategy()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name');
        }

        if (TradingStrategy::where('name', $name)->exists()) {
            throw new Exception("Strategy '{$name}' already exists");
        }

        $strategy = TradingStrategy::create([
            'name' => $name,
            'timeframe' => $this->option('timeframe'),
            'max_positions' => (int)$this->option('max-positions'),
            'max_drawdown' => (float)$this->option('max-drawdown'),
            'profit_target' => (float)$this->option('profit-target'),
            'stop_loss' => (float)$this->option('stop-loss'),
            'is_active' => false,
            'version' => '1.0.0',
            'parameters' => $this->getDefaultParameters(),
        ]);

        $this->info("Strategy '{$name}' created successfully");
    }

    /**
     * Update existing strategy
     */
    protected function updateStrategy()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name to update');
        }

        $strategy = TradingStrategy::where('name', $name)->firstOrFail();

        $strategy->update([
            'timeframe' => $this->option('timeframe'),
            'max_positions' => (int)$this->option('max-positions'),
            'max_drawdown' => (float)$this->option('max-drawdown'),
            'profit_target' => (float)$this->option('profit-target'),
            'stop_loss' => (float)$this->option('stop-loss'),
        ]);

        $strategy->incrementVersion();
        $strategy->logChange('UPDATE', 'Strategy parameters updated');

        $this->info("Strategy '{$name}' updated successfully");
    }

    /**
     * Delete strategy
     */
    protected function deleteStrategy()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name to delete');
        }

        $strategy = TradingStrategy::where('name', $name)->firstOrFail();

        // Check for open positions
        if (Position::where('strategy_name', $name)->where('status', Position::STATUS_OPEN)->exists()) {
            throw new Exception("Cannot delete strategy with open positions");
        }

        $strategy->delete();
        $this->info("Strategy '{$name}' deleted successfully");
    }

    /**
     * Enable strategy
     */
    protected function enableStrategy()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name to enable');
        }

        $strategy = TradingStrategy::where('name', $name)->firstOrFail();
        $strategy->update(['is_active' => true]);
        $strategy->logChange('ENABLE', 'Strategy enabled');

        $this->info("Strategy '{$name}' enabled");
    }

    /**
     * Disable strategy
     */
    protected function disableStrategy()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name to disable');
        }

        $strategy = TradingStrategy::where('name', $name)->firstOrFail();
        $strategy->update(['is_active' => false]);
        $strategy->logChange('DISABLE', 'Strategy disabled');

        $this->info("Strategy '{$name}' disabled");
    }

    /**
     * Show strategy metrics
     */
    protected function showMetrics()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter strategy name');
        }

        $strategy = TradingStrategy::where('name', $name)->firstOrFail();
        $metrics = $this->strategyExecution->getStrategyMetrics($strategy);

        $this->info("\nStrategy Metrics: {$name}");
        $this->line(str_repeat('-', 50));
        $this->line(sprintf("Total Trades: %d", $metrics['total_trades']));
        $this->line(sprintf("Win Rate: %.2f%%", $metrics['win_rate']));
        $this->line(sprintf("Profit Factor: %.2f", $metrics['profit_factor']));
        $this->line(sprintf("Average Win: %.2f USDT", $metrics['average_win']));
        $this->line(sprintf("Average Loss: %.2f USDT", $metrics['average_loss']));
        $this->line(sprintf("Largest Win: %.2f USDT", $metrics['largest_win']));
        $this->line(sprintf("Largest Loss: %.2f USDT", $metrics['largest_loss']));
        $this->line(sprintf("Total P&L: %.2f USDT", $metrics['total_pnl']));
    }

    /**
     * Get default strategy parameters
     */
    protected function getDefaultParameters(): array
    {
        return [
            'rsi_period' => 14,
            'rsi_overbought' => 70,
            'rsi_oversold' => 30,
            'macd_fast' => 12,
            'macd_slow' => 26,
            'macd_signal' => 9,
            'bb_period' => 20,
            'bb_deviation' => 2,
            'atr_period' => 14,
            'sentiment_threshold' => 0.5,
            'sentiment_confidence' => 0.7,
        ];
    }
}

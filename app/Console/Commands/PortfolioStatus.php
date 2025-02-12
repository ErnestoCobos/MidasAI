<?php

namespace App\Console\Commands;

use App\Models\Position;
use App\Models\TradingPair;
use App\Models\PortfolioSnapshot;
use App\Services\Binance\BinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Exception;

class PortfolioStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portfolio:status
                          {--pair= : Show status for specific trading pair}
                          {--watch : Watch mode with live updates}
                          {--interval=5 : Update interval in seconds for watch mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current portfolio status and performance metrics';

    protected BinanceService $binanceService;

    /**
     * Create a new command instance.
     */
    public function __construct(BinanceService $binanceService)
    {
        parent::__construct();
        $this->binanceService = $binanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            if ($this->option('watch')) {
                $this->watchPortfolio();
            } else {
                $this->displayPortfolioStatus();
            }
            return 0;
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display portfolio status
     */
    protected function displayPortfolioStatus()
    {
        // Get latest snapshot
        $snapshot = PortfolioSnapshot::latest('snapshot_time')->first();
        if (!$snapshot) {
            $this->warn('No portfolio data available');
            return;
        }

        // Display portfolio summary
        $this->info("\nPortfolio Summary");
        $this->line(str_repeat('-', 50));
        $this->line(sprintf("Total Value: %.2f USDT", $snapshot->total_value_usdt));
        $this->line(sprintf("Free Balance: %.2f USDT", $snapshot->free_usdt));
        $this->line(sprintf("Locked Balance: %.2f USDT", $snapshot->locked_usdt));
        
        // Display performance metrics
        $this->info("\nPerformance Metrics");
        $this->line(str_repeat('-', 50));
        $this->line(sprintf("Daily P&L: %.2f USDT (%.2f%%)", 
            $snapshot->daily_pnl,
            $snapshot->daily_pnl_percentage
        ));
        $this->line(sprintf("Total P&L: %.2f USDT (%.2f%%)",
            $snapshot->total_pnl,
            $snapshot->total_pnl_percentage
        ));
        $this->line(sprintf("Daily Drawdown: %.2f%%", $snapshot->daily_drawdown));
        $this->line(sprintf("Max Drawdown: %.2f%%", $snapshot->max_drawdown));

        // Display trading metrics
        $this->info("\nTrading Metrics");
        $this->line(str_repeat('-', 50));
        $this->line(sprintf("Total Trades: %d", $snapshot->total_trades));
        $this->line(sprintf("Win Rate: %.2f%%", $snapshot->win_rate));
        $this->line(sprintf("Profit Factor: %.2f", $snapshot->profit_factor));
        $this->line(sprintf("Average Win: %.2f USDT", $snapshot->average_win));
        $this->line(sprintf("Average Loss: %.2f USDT", $snapshot->average_loss));

        // Display open positions
        $this->displayOpenPositions();
    }

    /**
     * Display open positions
     */
    protected function displayOpenPositions()
    {
        $query = Position::where('status', Position::STATUS_OPEN);
        
        if ($pair = $this->option('pair')) {
            $query->whereHas('tradingPair', function ($q) use ($pair) {
                $q->where('symbol', $pair);
            });
        }

        $positions = $query->get();
        
        if ($positions->isEmpty()) {
            $this->info("\nNo open positions");
            return;
        }

        $this->info("\nOpen Positions");
        $this->line(str_repeat('-', 100));
        $this->line(sprintf("%-10s %-6s %-12s %-12s %-12s %-12s %-12s",
            "Symbol", "Side", "Size", "Entry", "Current", "P&L", "P&L %"
        ));
        $this->line(str_repeat('-', 100));

        foreach ($positions as $position) {
            $pnl = $position->unrealized_pnl;
            $pnlPercentage = $position->getPnLPercentage();
            $pnlColor = $pnl >= 0 ? 'green' : 'red';

            $this->line(sprintf("%-10s %-6s %-12.8f %-12.8f %-12.8f <fg=%s>%-12.2f</> <fg=%s>%-12.2f</>",
                $position->tradingPair->symbol,
                $position->side,
                $position->quantity,
                $position->entry_price,
                $position->current_price,
                $pnlColor,
                $pnl,
                $pnlColor,
                $pnlPercentage
            ));

            // Display stop loss and take profit if set
            if ($position->stop_loss || $position->take_profit) {
                $this->line(sprintf("          SL: %.8f  TP: %.8f",
                    $position->stop_loss ?? 0,
                    $position->take_profit ?? 0
                ));
            }
        }
    }

    /**
     * Watch portfolio in real-time
     */
    protected function watchPortfolio()
    {
        $interval = (int)$this->option('interval');
        
        while (true) {
            $this->output->write("\033[2J\033[;H"); // Clear screen
            $this->displayPortfolioStatus();
            $this->line("\nPress Ctrl+C to exit");
            sleep($interval);
        }
    }
}

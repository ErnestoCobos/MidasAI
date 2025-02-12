<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use App\Models\Position;
use App\Services\Binance\BinanceService;
use Illuminate\Console\Command;
use Exception;

class ManagePairs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pairs:manage
                          {action : Action to perform (list|add|update|remove|enable|disable|info)}
                          {--symbol= : Trading pair symbol (e.g., BTC/USDT)}
                          {--min-qty= : Minimum order quantity}
                          {--max-qty= : Maximum order quantity}
                          {--min-notional= : Minimum order value in USDT}
                          {--max-position= : Maximum position size}
                          {--maker-fee= : Maker fee percentage}
                          {--taker-fee= : Taker fee percentage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage trading pairs';

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
            $action = $this->argument('action');

            switch ($action) {
                case 'list':
                    $this->listPairs();
                    break;
                case 'add':
                    $this->addPair();
                    break;
                case 'update':
                    $this->updatePair();
                    break;
                case 'remove':
                    $this->removePair();
                    break;
                case 'enable':
                    $this->enablePair();
                    break;
                case 'disable':
                    $this->disablePair();
                    break;
                case 'info':
                    $this->showPairInfo();
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
     * List all trading pairs
     */
    protected function listPairs()
    {
        $pairs = TradingPair::all();
        
        if ($pairs->isEmpty()) {
            $this->info('No trading pairs found');
            return;
        }

        $this->info("\nTrading Pairs");
        $this->line(str_repeat('-', 100));
        $this->line(sprintf("%-12s %-8s %-15s %-15s %-15s %-15s",
            "Symbol", "Status", "Min Qty", "Max Qty", "Min Value", "Max Position"
        ));
        $this->line(str_repeat('-', 100));

        foreach ($pairs as $pair) {
            $this->line(sprintf("%-12s %-8s %-15.8f %-15.8f %-15.2f %-15.8f",
                $pair->symbol,
                $pair->is_active ? 'Active' : 'Inactive',
                $pair->min_qty,
                $pair->max_qty,
                $pair->min_notional,
                $pair->max_position_size
            ));
        }
    }

    /**
     * Add new trading pair
     */
    protected function addPair()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol (e.g., BTC/USDT)');
        }

        if (TradingPair::where('symbol', $symbol)->exists()) {
            throw new Exception("Trading pair '{$symbol}' already exists");
        }

        // Get exchange info from Binance
        $exchangeInfo = $this->binanceService->getExchangeInfo();
        $symbolInfo = collect($exchangeInfo['symbols'])
            ->where('symbol', str_replace('/', '', $symbol))
            ->first();

        if (!$symbolInfo) {
            throw new Exception("Symbol not found on Binance");
        }

        // Create trading pair
        $pair = TradingPair::create([
            'symbol' => $symbol,
            'base_asset' => $symbolInfo['baseAsset'],
            'quote_asset' => $symbolInfo['quoteAsset'],
            'min_qty' => $this->option('min-qty') ?? $this->getFilterValue($symbolInfo, 'LOT_SIZE', 'minQty'),
            'max_qty' => $this->option('max-qty') ?? $this->getFilterValue($symbolInfo, 'LOT_SIZE', 'maxQty'),
            'min_notional' => $this->option('min-notional') ?? $this->getFilterValue($symbolInfo, 'MIN_NOTIONAL', 'minNotional'),
            'max_position_size' => $this->option('max-position') ?? 0,
            'maker_fee' => $this->option('maker-fee') ?? 0.1,
            'taker_fee' => $this->option('taker-fee') ?? 0.1,
            'is_active' => false,
        ]);

        $this->info("Trading pair '{$symbol}' added successfully");
    }

    /**
     * Update trading pair
     */
    protected function updatePair()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol to update');
        }

        $pair = TradingPair::where('symbol', $symbol)->firstOrFail();

        $updates = [];
        if ($this->option('min-qty')) $updates['min_qty'] = $this->option('min-qty');
        if ($this->option('max-qty')) $updates['max_qty'] = $this->option('max-qty');
        if ($this->option('min-notional')) $updates['min_notional'] = $this->option('min-notional');
        if ($this->option('max-position')) $updates['max_position_size'] = $this->option('max-position');
        if ($this->option('maker-fee')) $updates['maker_fee'] = $this->option('maker-fee');
        if ($this->option('taker-fee')) $updates['taker_fee'] = $this->option('taker-fee');

        $pair->update($updates);
        $this->info("Trading pair '{$symbol}' updated successfully");
    }

    /**
     * Remove trading pair
     */
    protected function removePair()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol to remove');
        }

        $pair = TradingPair::where('symbol', $symbol)->firstOrFail();

        // Check for open positions
        if (Position::where('trading_pair_id', $pair->id)
            ->where('status', Position::STATUS_OPEN)
            ->exists()
        ) {
            throw new Exception("Cannot remove pair with open positions");
        }

        $pair->delete();
        $this->info("Trading pair '{$symbol}' removed successfully");
    }

    /**
     * Enable trading pair
     */
    protected function enablePair()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol to enable');
        }

        $pair = TradingPair::where('symbol', $symbol)->firstOrFail();
        $pair->update(['is_active' => true]);

        $this->info("Trading pair '{$symbol}' enabled");
    }

    /**
     * Disable trading pair
     */
    protected function disablePair()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol to disable');
        }

        $pair = TradingPair::where('symbol', $symbol)->firstOrFail();
        $pair->update(['is_active' => false]);

        $this->info("Trading pair '{$symbol}' disabled");
    }

    /**
     * Show trading pair information
     */
    protected function showPairInfo()
    {
        $symbol = $this->option('symbol');
        if (!$symbol) {
            $symbol = $this->ask('Enter trading pair symbol');
        }

        $pair = TradingPair::where('symbol', $symbol)->firstOrFail();
        
        // Get current price
        $price = $this->binanceService->getPrice($symbol);
        
        // Get 24hr ticker
        $ticker = $this->binanceService->get24hrTicker($symbol);

        $this->info("\nTrading Pair Information: {$symbol}");
        $this->line(str_repeat('-', 50));
        $this->line(sprintf("Status: %s", $pair->is_active ? 'Active' : 'Inactive'));
        $this->line(sprintf("Base Asset: %s", $pair->base_asset));
        $this->line(sprintf("Quote Asset: %s", $pair->quote_asset));
        $this->line(sprintf("Current Price: %.8f", $price));
        $this->line(sprintf("24h Change: %.2f%%", $ticker['priceChangePercent']));
        $this->line(sprintf("24h Volume: %.2f", $ticker['volume']));
        $this->line(sprintf("Min Quantity: %.8f", $pair->min_qty));
        $this->line(sprintf("Max Quantity: %.8f", $pair->max_qty));
        $this->line(sprintf("Min Notional: %.2f USDT", $pair->min_notional));
        $this->line(sprintf("Max Position: %.8f", $pair->max_position_size));
        $this->line(sprintf("Maker Fee: %.2f%%", $pair->maker_fee));
        $this->line(sprintf("Taker Fee: %.2f%%", $pair->taker_fee));

        // Show open positions
        $positions = Position::where('trading_pair_id', $pair->id)
            ->where('status', Position::STATUS_OPEN)
            ->get();

        if ($positions->isNotEmpty()) {
            $this->info("\nOpen Positions");
            $this->line(str_repeat('-', 50));
            foreach ($positions as $position) {
                $this->line(sprintf("%s %.8f @ %.8f", 
                    $position->side,
                    $position->quantity,
                    $position->entry_price
                ));
            }
        }
    }

    /**
     * Get filter value from Binance symbol info
     */
    protected function getFilterValue(array $symbolInfo, string $filterType, string $key): ?string
    {
        $filter = collect($symbolInfo['filters'])
            ->where('filterType', $filterType)
            ->first();

        return $filter[$key] ?? null;
    }
}

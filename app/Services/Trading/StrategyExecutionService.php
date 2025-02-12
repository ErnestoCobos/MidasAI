<?php

namespace App\Services\Trading;

use App\Models\TradingPair;
use App\Models\TradingStrategy;
use App\Models\Position;
use App\Models\Order;
use App\Models\SystemLog;
use App\Services\Analysis\TechnicalAnalysisService;
use App\Services\Analysis\SentimentAnalysisService;
use App\Services\Binance\BinanceService;
use Illuminate\Support\Facades\Cache;
use Exception;

class StrategyExecutionService
{
    protected BinanceService $binanceService;
    protected TechnicalAnalysisService $technicalAnalysis;
    protected SentimentAnalysisService $sentimentAnalysis;
    protected RiskManagementService $riskManagement;

    public function __construct(
        BinanceService $binanceService,
        TechnicalAnalysisService $technicalAnalysis,
        SentimentAnalysisService $sentimentAnalysis,
        RiskManagementService $riskManagement
    ) {
        $this->binanceService = $binanceService;
        $this->technicalAnalysis = $technicalAnalysis;
        $this->sentimentAnalysis = $sentimentAnalysis;
        $this->riskManagement = $riskManagement;
    }

    /**
     * Execute trading strategy
     */
    public function executeStrategy(TradingStrategy $strategy, TradingPair $tradingPair): void
    {
        try {
            // Check if strategy is active
            if (!$strategy->is_active) {
                return;
            }

            // Check if within trading hours
            if (!$strategy->isWithinTradingHours()) {
                return;
            }

            // Update technical indicators
            $this->technicalAnalysis->updateIndicators($tradingPair);

            // Update sentiment analysis
            $this->sentimentAnalysis->analyzeSentiment($tradingPair);

            // Get current price
            $currentPrice = $this->binanceService->getPrice($tradingPair->symbol);
            if (!$currentPrice) {
                throw new Exception("Could not get current price for {$tradingPair->symbol}");
            }

            // Check existing positions
            $openPosition = Position::where('trading_pair_id', $tradingPair->id)
                ->where('status', Position::STATUS_OPEN)
                ->first();

            if ($openPosition) {
                $this->manageOpenPosition($strategy, $openPosition, $currentPrice);
            } else {
                $this->checkForNewPosition($strategy, $tradingPair, $currentPrice);
            }

        } catch (Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'StrategyExecutionService',
                'event' => 'STRATEGY_EXECUTION_FAILED',
                'message' => $e->getMessage(),
                'context' => [
                    'strategy' => $strategy->name,
                    'trading_pair' => $tradingPair->symbol
                ]
            ]);
        }
    }

    /**
     * Manage open position
     */
    protected function manageOpenPosition(TradingStrategy $strategy, Position $position, float $currentPrice): void
    {
        // Update position current price
        $position->current_price = $currentPrice;
        $position->save();

        // Update trailing stop if enabled
        $this->riskManagement->updateTrailingStop($position, $currentPrice);

        // Check if position should be closed
        $closeCheck = $this->riskManagement->shouldClosePosition($position, $currentPrice);
        if ($closeCheck['should_close']) {
            $this->closePosition($position, $currentPrice, $closeCheck['reason']);
            return;
        }

        // Check exit signals
        if ($this->checkExitSignals($strategy, $position)) {
            $this->closePosition($position, $currentPrice, 'Exit signals triggered');
            return;
        }
    }

    /**
     * Check for new position opportunities
     */
    protected function checkForNewPosition(TradingStrategy $strategy, TradingPair $tradingPair, float $currentPrice): void
    {
        // Check if strategy can open new position
        if (!$strategy->canOpenNewPosition()) {
            return;
        }

        // Get entry signals
        $signals = $this->analyzeEntrySignals($strategy, $tradingPair);
        if (!$signals['should_enter']) {
            return;
        }

        // Calculate position parameters
        $stopLoss = $this->riskManagement->calculateStopLoss($tradingPair, $currentPrice, $signals['side']);
        $size = $this->riskManagement->calculatePositionSize($tradingPair, $currentPrice, $stopLoss);
        
        // Check risk management
        $riskCheck = $this->riskManagement->canOpenPosition($tradingPair, $size, $signals['side']);
        if (!$riskCheck['allowed']) {
            return;
        }

        // Open position
        $this->openPosition($strategy, $tradingPair, $signals['side'], $size, $currentPrice, $stopLoss);
    }

    /**
     * Analyze entry signals
     */
    protected function analyzeEntrySignals(TradingStrategy $strategy, TradingPair $tradingPair): array
    {
        $indicators = $this->technicalAnalysis->getLatestIndicators($tradingPair->id);
        $sentiment = $this->sentimentAnalysis->getAggregateSentiment($tradingPair);
        
        // Default response
        $response = ['should_enter' => false, 'side' => null];

        // Check RSI conditions
        if ($this->technicalAnalysis->isOversold($tradingPair->id) && $sentiment['score'] > 0) {
            $response = ['should_enter' => true, 'side' => 'BUY'];
        } elseif ($this->technicalAnalysis->isOverbought($tradingPair->id) && $sentiment['score'] < 0) {
            $response = ['should_enter' => true, 'side' => 'SELL'];
        }

        // Check MACD crossovers
        if ($this->technicalAnalysis->hasBullishMACDCrossover($tradingPair->id) && $sentiment['score'] > 0) {
            $response = ['should_enter' => true, 'side' => 'BUY'];
        } elseif ($this->technicalAnalysis->hasBearishMACDCrossover($tradingPair->id) && $sentiment['score'] < 0) {
            $response = ['should_enter' => true, 'side' => 'SELL'];
        }

        // Check Bollinger Bands
        if ($this->technicalAnalysis->isPriceBelowBB($tradingPair->id, $tradingPair->getLatestPrice()) && $sentiment['score'] > 0) {
            $response = ['should_enter' => true, 'side' => 'BUY'];
        } elseif ($this->technicalAnalysis->isPriceAboveBB($tradingPair->id, $tradingPair->getLatestPrice()) && $sentiment['score'] < 0) {
            $response = ['should_enter' => true, 'side' => 'SELL'];
        }

        return $response;
    }

    /**
     * Check exit signals
     */
    protected function checkExitSignals(TradingStrategy $strategy, Position $position): bool
    {
        $tradingPair = $position->tradingPair;
        $indicators = $this->technicalAnalysis->getLatestIndicators($tradingPair->id);
        $sentiment = $this->sentimentAnalysis->getAggregateSentiment($tradingPair);

        if ($position->side === Position::SIDE_LONG) {
            // Exit long positions
            if ($this->technicalAnalysis->isOverbought($tradingPair->id) || 
                $this->technicalAnalysis->hasBearishMACDCrossover($tradingPair->id) ||
                ($sentiment['score'] < -0.5 && $sentiment['confidence'] > 0.7)) {
                return true;
            }
        } else {
            // Exit short positions
            if ($this->technicalAnalysis->isOversold($tradingPair->id) || 
                $this->technicalAnalysis->hasBullishMACDCrossover($tradingPair->id) ||
                ($sentiment['score'] > 0.5 && $sentiment['confidence'] > 0.7)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Open new position
     */
    protected function openPosition(
        TradingStrategy $strategy,
        TradingPair $tradingPair,
        string $side,
        float $size,
        float $entryPrice,
        float $stopLoss
    ): void {
        try {
            // Place market order
            $order = $side === 'BUY' 
                ? $this->binanceService->marketBuy($tradingPair->symbol, $size)
                : $this->binanceService->marketSell($tradingPair->symbol, $size);

            // Calculate take profit
            $takeProfit = $this->riskManagement->calculateTakeProfit($entryPrice, $stopLoss);

            // Create position record
            $position = Position::create([
                'trading_pair_id' => $tradingPair->id,
                'side' => $side === 'BUY' ? Position::SIDE_LONG : Position::SIDE_SHORT,
                'status' => Position::STATUS_OPEN,
                'quantity' => $size,
                'entry_price' => $entryPrice,
                'current_price' => $entryPrice,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
                'strategy_name' => $strategy->name,
                'opened_at' => now(),
            ]);

            // Log position opening
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'StrategyExecutionService',
                'event' => 'POSITION_OPENED',
                'message' => "Opened {$side} position for {$tradingPair->symbol}",
                'context' => [
                    'position_id' => $position->id,
                    'size' => $size,
                    'entry_price' => $entryPrice,
                    'stop_loss' => $stopLoss,
                    'take_profit' => $takeProfit
                ]
            ]);

        } catch (Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'StrategyExecutionService',
                'event' => 'POSITION_OPEN_FAILED',
                'message' => $e->getMessage(),
                'context' => [
                    'trading_pair' => $tradingPair->symbol,
                    'side' => $side,
                    'size' => $size
                ]
            ]);
        }
    }

    /**
     * Close position
     */
    protected function closePosition(Position $position, float $exitPrice, string $reason): void
    {
        try {
            // Place market order
            $order = $position->side === Position::SIDE_LONG
                ? $this->binanceService->marketSell($position->tradingPair->symbol, $position->quantity)
                : $this->binanceService->marketBuy($position->tradingPair->symbol, $position->quantity);

            // Update position
            $position->status = Position::STATUS_CLOSED;
            $position->current_price = $exitPrice;
            $position->closed_at = now();
            $position->save();

            // Calculate final P&L
            $pnl = $position->getUnrealizedPnL($exitPrice);

            // Log position closing
            SystemLog::create([
                'level' => 'INFO',
                'component' => 'StrategyExecutionService',
                'event' => 'POSITION_CLOSED',
                'message' => "Closed position for {$position->tradingPair->symbol}",
                'context' => [
                    'position_id' => $position->id,
                    'reason' => $reason,
                    'exit_price' => $exitPrice,
                    'pnl' => $pnl
                ]
            ]);

        } catch (Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'StrategyExecutionService',
                'event' => 'POSITION_CLOSE_FAILED',
                'message' => $e->getMessage(),
                'context' => [
                    'position_id' => $position->id,
                    'trading_pair' => $position->tradingPair->symbol
                ]
            ]);
        }
    }

    /**
     * Get strategy performance metrics
     */
    public function getStrategyMetrics(TradingStrategy $strategy): array
    {
        $cacheKey = "strategy_metrics_{$strategy->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($strategy) {
            $positions = Position::where('strategy_name', $strategy->name)
                ->where('status', Position::STATUS_CLOSED)
                ->get();

            if ($positions->isEmpty()) {
                return [
                    'total_trades' => 0,
                    'win_rate' => 0,
                    'profit_factor' => 0,
                    'average_win' => 0,
                    'average_loss' => 0,
                    'largest_win' => 0,
                    'largest_loss' => 0,
                    'total_pnl' => 0
                ];
            }

            $wins = $positions->filter(fn($p) => $p->realized_pnl > 0);
            $losses = $positions->filter(fn($p) => $p->realized_pnl < 0);

            $totalWins = $wins->sum('realized_pnl');
            $totalLosses = abs($losses->sum('realized_pnl'));

            return [
                'total_trades' => $positions->count(),
                'win_rate' => $positions->count() > 0 ? ($wins->count() / $positions->count()) * 100 : 0,
                'profit_factor' => $totalLosses > 0 ? $totalWins / $totalLosses : 0,
                'average_win' => $wins->count() > 0 ? $totalWins / $wins->count() : 0,
                'average_loss' => $losses->count() > 0 ? $totalLosses / $losses->count() : 0,
                'largest_win' => $wins->max('realized_pnl') ?? 0,
                'largest_loss' => $losses->min('realized_pnl') ?? 0,
                'total_pnl' => $positions->sum('realized_pnl')
            ];
        });
    }
}

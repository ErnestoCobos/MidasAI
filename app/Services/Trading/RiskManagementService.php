<?php

namespace App\Services\Trading;

use App\Models\Position;
use App\Models\TradingPair;
use App\Models\PortfolioSnapshot;
use App\Models\SystemLog;
use App\Services\Analysis\TechnicalAnalysisService;
use Illuminate\Support\Facades\Cache;

class RiskManagementService
{
    protected TechnicalAnalysisService $technicalAnalysis;
    
    // Risk thresholds
    protected const MAX_PORTFOLIO_RISK = 0.05; // 5% max portfolio risk
    protected const MAX_POSITION_RISK = 0.02;  // 2% max position risk
    protected const MAX_PAIR_EXPOSURE = 0.20;  // 20% max exposure per pair
    protected const MAX_DRAWDOWN = 0.15;       // 15% max drawdown
    protected const VOLATILITY_SCALING = true; // Scale position sizes by volatility
    
    public function __construct(TechnicalAnalysisService $technicalAnalysis)
    {
        $this->technicalAnalysis = $technicalAnalysis;
    }

    /**
     * Check if a new position can be opened
     */
    public function canOpenPosition(TradingPair $tradingPair, float $size, string $side): array
    {
        try {
            // Check portfolio risk
            if (!$this->checkPortfolioRisk()) {
                return [
                    'allowed' => false,
                    'reason' => 'Portfolio risk limit exceeded'
                ];
            }

            // Check pair exposure
            if (!$this->checkPairExposure($tradingPair, $size)) {
                return [
                    'allowed' => false,
                    'reason' => 'Maximum pair exposure reached'
                ];
            }

            // Check drawdown
            if (!$this->checkDrawdown()) {
                return [
                    'allowed' => false,
                    'reason' => 'Maximum drawdown reached'
                ];
            }

            // Check volatility
            if (!$this->checkVolatility($tradingPair)) {
                return [
                    'allowed' => false,
                    'reason' => 'Volatility too high'
                ];
            }

            return ['allowed' => true];
        } catch (\Exception $e) {
            SystemLog::create([
                'level' => 'ERROR',
                'component' => 'RiskManagementService',
                'event' => 'RISK_CHECK_FAILED',
                'message' => $e->getMessage(),
                'context' => [
                    'trading_pair' => $tradingPair->symbol,
                    'size' => $size,
                    'side' => $side
                ]
            ]);

            return [
                'allowed' => false,
                'reason' => 'Risk check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate position size based on risk parameters
     */
    public function calculatePositionSize(TradingPair $tradingPair, float $price, float $stopLoss): float
    {
        // Get portfolio value
        $portfolioValue = $this->getPortfolioValue();
        
        // Calculate risk amount (2% of portfolio)
        $riskAmount = $portfolioValue * self::MAX_POSITION_RISK;
        
        // Calculate position size based on stop loss distance
        $stopDistance = abs($price - $stopLoss);
        $baseSize = $riskAmount / $stopDistance;
        
        // Apply volatility scaling if enabled
        if (self::VOLATILITY_SCALING) {
            $baseSize = $this->scaleByVolatility($tradingPair, $baseSize);
        }
        
        // Ensure within pair limits
        $baseSize = min($baseSize, $tradingPair->max_position_size);
        $baseSize = max($baseSize, $tradingPair->min_qty);
        
        return $baseSize;
    }

    /**
     * Calculate stop loss price based on ATR
     */
    public function calculateStopLoss(TradingPair $tradingPair, float $entryPrice, string $side): float
    {
        $atr = $this->technicalAnalysis->getLatestIndicators($tradingPair->id)['atr'] ?? 0;
        
        if ($atr === 0) {
            // Fallback to fixed percentage if no ATR available
            return $side === 'BUY' 
                ? $entryPrice * 0.95  // 5% below entry for longs
                : $entryPrice * 1.05; // 5% above entry for shorts
        }

        $multiplier = 2; // ATR multiplier
        return $side === 'BUY'
            ? $entryPrice - ($atr * $multiplier)
            : $entryPrice + ($atr * $multiplier);
    }

    /**
     * Calculate take profit price based on R:R ratio
     */
    public function calculateTakeProfit(float $entryPrice, float $stopLoss, float $riskRewardRatio = 2): float
    {
        $risk = abs($entryPrice - $stopLoss);
        $reward = $risk * $riskRewardRatio;
        
        return $stopLoss < $entryPrice
            ? $entryPrice + $reward  // Long position
            : $entryPrice - $reward; // Short position
    }

    /**
     * Check if portfolio risk is within limits
     */
    protected function checkPortfolioRisk(): bool
    {
        $openPositions = Position::where('status', Position::STATUS_OPEN)->get();
        $totalRisk = 0;
        
        foreach ($openPositions as $position) {
            $risk = abs($position->entry_price - $position->stop_loss) * $position->quantity;
            $totalRisk += $risk;
        }
        
        $portfolioValue = $this->getPortfolioValue();
        return ($totalRisk / $portfolioValue) <= self::MAX_PORTFOLIO_RISK;
    }

    /**
     * Check if pair exposure is within limits
     */
    protected function checkPairExposure(TradingPair $tradingPair, float $additionalSize): bool
    {
        $currentExposure = Position::where('trading_pair_id', $tradingPair->id)
            ->where('status', Position::STATUS_OPEN)
            ->sum('quantity');
            
        $portfolioValue = $this->getPortfolioValue();
        $totalExposure = ($currentExposure + $additionalSize) * $tradingPair->getLatestPrice();
        
        return ($totalExposure / $portfolioValue) <= self::MAX_PAIR_EXPOSURE;
    }

    /**
     * Check if drawdown is within limits
     */
    protected function checkDrawdown(): bool
    {
        $snapshot = PortfolioSnapshot::latest('snapshot_time')->first();
        if (!$snapshot) {
            return true;
        }
        
        return abs($snapshot->daily_drawdown) <= self::MAX_DRAWDOWN;
    }

    /**
     * Check if volatility is acceptable
     */
    protected function checkVolatility(TradingPair $tradingPair): bool
    {
        $indicators = $this->technicalAnalysis->getLatestIndicators($tradingPair->id);
        $volatility = $indicators['volatility'] ?? 0;
        
        // Consider market too volatile if above 50%
        return $volatility <= 0.5;
    }

    /**
     * Scale position size based on volatility
     */
    protected function scaleByVolatility(TradingPair $tradingPair, float $baseSize): float
    {
        $indicators = $this->technicalAnalysis->getLatestIndicators($tradingPair->id);
        $volatility = $indicators['volatility'] ?? 0.2; // Default to 20% if not available
        
        // Reduce position size as volatility increases
        $scaleFactor = 1 - min($volatility, 0.5);
        return $baseSize * $scaleFactor;
    }

    /**
     * Get current portfolio value
     */
    protected function getPortfolioValue(): float
    {
        $snapshot = PortfolioSnapshot::latest('snapshot_time')->first();
        return $snapshot ? $snapshot->total_value_usdt : 0;
    }

    /**
     * Update trailing stop for a position
     */
    public function updateTrailingStop(Position $position, float $currentPrice): void
    {
        if (!$position->trailing_stop) {
            return;
        }

        $atr = $this->technicalAnalysis->getLatestIndicators($position->trading_pair_id)['atr'] ?? 0;
        if ($atr === 0) {
            return;
        }

        $multiplier = 2; // ATR multiplier
        $stopDistance = $atr * $multiplier;

        if ($position->side === Position::SIDE_LONG) {
            $newStop = $currentPrice - $stopDistance;
            if ($newStop > $position->trailing_stop) {
                $position->trailing_stop = $newStop;
                $position->save();
            }
        } else {
            $newStop = $currentPrice + $stopDistance;
            if ($newStop < $position->trailing_stop) {
                $position->trailing_stop = $newStop;
                $position->save();
            }
        }
    }

    /**
     * Check if position should be closed based on risk metrics
     */
    public function shouldClosePosition(Position $position, float $currentPrice): array
    {
        // Check stop loss
        if ($position->stop_loss) {
            if ($position->side === Position::SIDE_LONG && $currentPrice <= $position->stop_loss) {
                return [
                    'should_close' => true,
                    'reason' => 'Stop loss triggered'
                ];
            }
            if ($position->side === Position::SIDE_SHORT && $currentPrice >= $position->stop_loss) {
                return [
                    'should_close' => true,
                    'reason' => 'Stop loss triggered'
                ];
            }
        }

        // Check trailing stop
        if ($position->trailing_stop) {
            if ($position->side === Position::SIDE_LONG && $currentPrice <= $position->trailing_stop) {
                return [
                    'should_close' => true,
                    'reason' => 'Trailing stop triggered'
                ];
            }
            if ($position->side === Position::SIDE_SHORT && $currentPrice >= $position->trailing_stop) {
                return [
                    'should_close' => true,
                    'reason' => 'Trailing stop triggered'
                ];
            }
        }

        // Check take profit
        if ($position->take_profit) {
            if ($position->side === Position::SIDE_LONG && $currentPrice >= $position->take_profit) {
                return [
                    'should_close' => true,
                    'reason' => 'Take profit reached'
                ];
            }
            if ($position->side === Position::SIDE_SHORT && $currentPrice <= $position->take_profit) {
                return [
                    'should_close' => true,
                    'reason' => 'Take profit reached'
                ];
            }
        }

        // Check max drawdown
        $unrealizedPnL = $position->getUnrealizedPnL($currentPrice);
        $entryValue = $position->quantity * $position->entry_price;
        $drawdown = $unrealizedPnL / $entryValue;
        
        if (abs($drawdown) >= self::MAX_DRAWDOWN) {
            return [
                'should_close' => true,
                'reason' => 'Maximum position drawdown reached'
            ];
        }

        return ['should_close' => false];
    }

    /**
     * Get risk metrics for a trading pair
     */
    public function getRiskMetrics(TradingPair $tradingPair): array
    {
        $cacheKey = "risk_metrics_{$tradingPair->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($tradingPair) {
            $indicators = $this->technicalAnalysis->getLatestIndicators($tradingPair->id);
            $positions = Position::where('trading_pair_id', $tradingPair->id)
                ->where('status', Position::STATUS_OPEN)
                ->get();
            
            $exposure = 0;
            $unrealizedPnL = 0;
            foreach ($positions as $position) {
                $exposure += $position->quantity * $position->current_price;
                $unrealizedPnL += $position->unrealized_pnl;
            }
            
            return [
                'volatility' => $indicators['volatility'] ?? 0,
                'atr' => $indicators['atr'] ?? 0,
                'exposure' => $exposure,
                'exposure_pct' => $exposure / $this->getPortfolioValue(),
                'unrealized_pnl' => $unrealizedPnL,
                'position_count' => $positions->count(),
                'max_position_size' => $this->calculateMaxPositionSize($tradingPair)
            ];
        });
    }

    /**
     * Calculate maximum position size for a trading pair
     */
    protected function calculateMaxPositionSize(TradingPair $tradingPair): float
    {
        $portfolioValue = $this->getPortfolioValue();
        $maxSize = $portfolioValue * self::MAX_PAIR_EXPOSURE;
        
        // Scale by volatility if enabled
        if (self::VOLATILITY_SCALING) {
            $maxSize = $this->scaleByVolatility($tradingPair, $maxSize);
        }
        
        return min($maxSize, $tradingPair->max_position_size);
    }
}

<?php

namespace App\Services\Analysis;

use App\Models\MarketData;
use App\Models\TechnicalIndicator;
use App\Models\TradingPair;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TechnicalAnalysisService
{
    /**
     * Calculate RSI
     */
    public function calculateRSI(array $prices, int $period = 14): float
    {
        if (count($prices) < $period) {
            return 50.0;
        }

        $gains = [];
        $losses = [];
        
        // Calculate price changes
        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            if ($change >= 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        // Calculate average gain and loss
        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        // Calculate RS and RSI
        if ($avgLoss == 0) {
            return 100;
        }
        
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    /**
     * Calculate MACD
     */
    public function calculateMACD(array $prices, int $fastPeriod = 12, int $slowPeriod = 26, int $signalPeriod = 9): array
    {
        $fastEMA = $this->calculateEMA($prices, $fastPeriod);
        $slowEMA = $this->calculateEMA($prices, $slowPeriod);
        
        $macdLine = array_map(function ($fast, $slow) {
            return $fast - $slow;
        }, $fastEMA, $slowEMA);
        
        $signalLine = $this->calculateEMA($macdLine, $signalPeriod);
        
        $histogram = array_map(function ($macd, $signal) {
            return $macd - $signal;
        }, $macdLine, $signalLine);

        return [
            'macd_line' => end($macdLine),
            'signal_line' => end($signalLine),
            'histogram' => end($histogram)
        ];
    }

    /**
     * Calculate Bollinger Bands
     */
    public function calculateBollingerBands(array $prices, int $period = 20, float $multiplier = 2.0): array
    {
        $sma = $this->calculateSMA($prices, $period);
        $stdDev = $this->calculateStandardDeviation($prices, $period);
        
        $upperBand = $sma + ($multiplier * $stdDev);
        $lowerBand = $sma - ($multiplier * $stdDev);

        return [
            'upper' => $upperBand,
            'middle' => $sma,
            'lower' => $lowerBand
        ];
    }

    /**
     * Calculate ATR (Average True Range)
     */
    public function calculateATR(array $high, array $low, array $close, int $period = 14): float
    {
        $trueRanges = [];
        
        for ($i = 1; $i < count($high); $i++) {
            $tr1 = $high[$i] - $low[$i];
            $tr2 = abs($high[$i] - $close[$i - 1]);
            $tr3 = abs($low[$i] - $close[$i - 1]);
            
            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum(array_slice($trueRanges, -$period)) / $period;
    }

    /**
     * Calculate EMA (Exponential Moving Average)
     */
    protected function calculateEMA(array $prices, int $period): array
    {
        $multiplier = 2 / ($period + 1);
        $ema = [$prices[0]];
        
        for ($i = 1; $i < count($prices); $i++) {
            $ema[] = ($prices[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }

        return $ema;
    }

    /**
     * Calculate SMA (Simple Moving Average)
     */
    protected function calculateSMA(array $prices, int $period): float
    {
        return array_sum(array_slice($prices, -$period)) / $period;
    }

    /**
     * Calculate Standard Deviation
     */
    protected function calculateStandardDeviation(array $prices, int $period): float
    {
        $slice = array_slice($prices, -$period);
        $mean = array_sum($slice) / $period;
        
        $squaredDiffs = array_map(function ($price) use ($mean) {
            return pow($price - $mean, 2);
        }, $slice);
        
        return sqrt(array_sum($squaredDiffs) / $period);
    }

    /**
     * Update technical indicators for a trading pair
     */
    public function updateIndicators(TradingPair $tradingPair): void
    {
        // Get recent market data
        $marketData = MarketData::where('trading_pair_id', $tradingPair->id)
            ->orderBy('timestamp', 'desc')
            ->limit(50)
            ->get();

        if ($marketData->isEmpty()) {
            return;
        }

        // Prepare price arrays
        $closes = $marketData->pluck('close')->toArray();
        $highs = $marketData->pluck('high')->toArray();
        $lows = $marketData->pluck('low')->toArray();

        // Calculate indicators
        $rsi = $this->calculateRSI($closes);
        $macd = $this->calculateMACD($closes);
        $bb = $this->calculateBollingerBands($closes);
        $atr = $this->calculateATR($highs, $lows, $closes);
        $volatility = $this->calculateVolatility($closes);
        $sma20 = $this->calculateSMA($closes, 20);
        $ema20 = end($this->calculateEMA($closes, 20));

        // Save indicators
        TechnicalIndicator::create([
            'trading_pair_id' => $tradingPair->id,
            'timestamp' => now(),
            'rsi' => $rsi,
            'macd_line' => $macd['macd_line'],
            'macd_signal' => $macd['signal_line'],
            'macd_histogram' => $macd['histogram'],
            'bb_upper' => $bb['upper'],
            'bb_middle' => $bb['middle'],
            'bb_lower' => $bb['lower'],
            'atr' => $atr,
            'volatility' => $volatility,
            'sma_20' => $sma20,
            'ema_20' => $ema20,
        ]);

        // Update cache
        $this->updateCache($tradingPair->id, [
            'rsi' => $rsi,
            'macd' => $macd,
            'bb' => $bb,
            'atr' => $atr,
            'volatility' => $volatility,
            'sma_20' => $sma20,
            'ema_20' => $ema20,
        ]);
    }

    /**
     * Calculate volatility (standard deviation of returns)
     */
    protected function calculateVolatility(array $prices, int $period = 20): float
    {
        $returns = [];
        for ($i = 1; $i < count($prices); $i++) {
            $returns[] = ($prices[$i] - $prices[$i - 1]) / $prices[$i - 1];
        }

        return $this->calculateStandardDeviation($returns, min(count($returns), $period));
    }

    /**
     * Update cache with latest indicators
     */
    protected function updateCache(int $tradingPairId, array $indicators): void
    {
        $cacheKey = "technical_indicators_{$tradingPairId}";
        Cache::put($cacheKey, $indicators, 60);
    }

    /**
     * Get latest indicators from cache or database
     */
    public function getLatestIndicators(int $tradingPairId): array
    {
        $cacheKey = "technical_indicators_{$tradingPairId}";
        
        return Cache::remember($cacheKey, 60, function () use ($tradingPairId) {
            $indicators = TechnicalIndicator::where('trading_pair_id', $tradingPairId)
                ->latest('timestamp')
                ->first();

            if (!$indicators) {
                return [];
            }

            return [
                'rsi' => $indicators->rsi,
                'macd' => [
                    'macd_line' => $indicators->macd_line,
                    'signal_line' => $indicators->macd_signal,
                    'histogram' => $indicators->macd_histogram,
                ],
                'bb' => [
                    'upper' => $indicators->bb_upper,
                    'middle' => $indicators->bb_middle,
                    'lower' => $indicators->bb_lower,
                ],
                'atr' => $indicators->atr,
                'volatility' => $indicators->volatility,
                'sma_20' => $indicators->sma_20,
                'ema_20' => $indicators->ema_20,
            ];
        });
    }

    /**
     * Check if price is in oversold territory
     */
    public function isOversold(int $tradingPairId, float $threshold = 30): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['rsi']) && $indicators['rsi'] <= $threshold;
    }

    /**
     * Check if price is in overbought territory
     */
    public function isOverbought(int $tradingPairId, float $threshold = 70): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['rsi']) && $indicators['rsi'] >= $threshold;
    }

    /**
     * Check for bullish MACD crossover
     */
    public function hasBullishMACDCrossover(int $tradingPairId): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['macd']) && 
               $indicators['macd']['histogram'] > 0 && 
               $indicators['macd']['macd_line'] > $indicators['macd']['signal_line'];
    }

    /**
     * Check for bearish MACD crossover
     */
    public function hasBearishMACDCrossover(int $tradingPairId): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['macd']) && 
               $indicators['macd']['histogram'] < 0 && 
               $indicators['macd']['macd_line'] < $indicators['macd']['signal_line'];
    }

    /**
     * Check if price is above Bollinger upper band
     */
    public function isPriceAboveBB(int $tradingPairId, float $price): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['bb']) && $price > $indicators['bb']['upper'];
    }

    /**
     * Check if price is below Bollinger lower band
     */
    public function isPriceBelowBB(int $tradingPairId, float $price): bool
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        return isset($indicators['bb']) && $price < $indicators['bb']['lower'];
    }

    /**
     * Get suggested position size based on volatility
     */
    public function getSuggestedPositionSize(int $tradingPairId, float $baseSize): float
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        
        if (!isset($indicators['volatility'])) {
            return $baseSize;
        }

        // Reduce position size as volatility increases
        $volatilityFactor = 1 - min($indicators['volatility'], 0.5);
        return $baseSize * $volatilityFactor;
    }

    /**
     * Get suggested stop loss based on ATR
     */
    public function getSuggestedStopLoss(int $tradingPairId, float $entryPrice, string $side): float
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        
        if (!isset($indicators['atr'])) {
            return $side === 'BUY' ? $entryPrice * 0.95 : $entryPrice * 1.05;
        }

        $atrMultiplier = 2;
        return $side === 'BUY' 
            ? $entryPrice - ($indicators['atr'] * $atrMultiplier)
            : $entryPrice + ($indicators['atr'] * $atrMultiplier);
    }

    /**
     * Get trend strength (-100 to 100)
     */
    public function getTrendStrength(int $tradingPairId): float
    {
        $indicators = $this->getLatestIndicators($tradingPairId);
        
        if (!isset($indicators['ema_20']) || !isset($indicators['sma_20'])) {
            return 0;
        }

        // Calculate percentage difference between EMA and SMA
        $diff = ($indicators['ema_20'] - $indicators['sma_20']) / $indicators['sma_20'] * 100;
        
        // Normalize to -100 to 100 range
        return max(-100, min(100, $diff * 10));
    }
}

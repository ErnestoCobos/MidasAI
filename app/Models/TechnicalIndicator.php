<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalIndicator extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'timestamp',
        'rsi',
        'macd_line',
        'macd_signal',
        'macd_histogram',
        'bb_upper',
        'bb_middle',
        'bb_lower',
        'atr',
        'volatility',
        'sma_20',
        'ema_20',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'rsi' => 'decimal:4',
        'macd_line' => 'decimal:8',
        'macd_signal' => 'decimal:8',
        'macd_histogram' => 'decimal:8',
        'bb_upper' => 'decimal:8',
        'bb_middle' => 'decimal:8',
        'bb_lower' => 'decimal:8',
        'atr' => 'decimal:8',
        'volatility' => 'decimal:4',
        'sma_20' => 'decimal:8',
        'ema_20' => 'decimal:8',
    ];

    // Relationships
    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class);
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    public function scopeTimeRange($query, $start, $end)
    {
        return $query->whereBetween('timestamp', [$start, $end]);
    }

    // Helper Methods
    public function isBullishMACD()
    {
        return $this->macd_histogram > 0;
    }

    public function isBearishMACD()
    {
        return $this->macd_histogram < 0;
    }

    public function isOverbought($threshold = 70)
    {
        return $this->rsi >= $threshold;
    }

    public function isOversold($threshold = 30)
    {
        return $this->rsi <= $threshold;
    }

    public function isPriceAboveBB($price)
    {
        return $price > $this->bb_upper;
    }

    public function isPriceBelowBB($price)
    {
        return $price < $this->bb_lower;
    }

    public function getBBWidth()
    {
        return ($this->bb_upper - $this->bb_lower) / $this->bb_middle;
    }

    public function isBBSqueeze($threshold = 0.1)
    {
        return $this->getBBWidth() < $threshold;
    }

    public function getTrendStrength()
    {
        // Simple trend strength calculation based on EMAs
        if ($this->ema_20 > $this->sma_20) {
            return ($this->ema_20 - $this->sma_20) / $this->sma_20 * 100;
        }
        return ($this->ema_20 - $this->sma_20) / $this->sma_20 * -100;
    }
}

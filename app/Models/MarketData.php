<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketData extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'quote_volume',
        'number_of_trades',
        'taker_buy_volume',
        'taker_buy_quote_volume',
        'daily_volatility',
        'buy_sell_ratio',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
        'quote_volume' => 'decimal:8',
        'number_of_trades' => 'integer',
        'taker_buy_volume' => 'decimal:8',
        'taker_buy_quote_volume' => 'decimal:8',
        'daily_volatility' => 'decimal:8',
        'buy_sell_ratio' => 'decimal:8',
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

    public function scopeLastNCandles($query, $n)
    {
        return $query->latest()->limit($n);
    }

    // Helper Methods
    public function getRange()
    {
        return $this->high - $this->low;
    }

    public function getTrueRange($previousClose = null)
    {
        if (!$previousClose) {
            return $this->getRange();
        }

        return max(
            $this->high - $this->low,
            abs($this->high - $previousClose),
            abs($this->low - $previousClose)
        );
    }

    public function getBodySize()
    {
        return abs($this->close - $this->open);
    }

    public function getUpperShadow()
    {
        return $this->high - max($this->open, $this->close);
    }

    public function getLowerShadow()
    {
        return min($this->open, $this->close) - $this->low;
    }

    public function isBullish()
    {
        return $this->close > $this->open;
    }

    public function isBearish()
    {
        return $this->close < $this->open;
    }

    public function isDoji($threshold = 0.1)
    {
        return $this->getBodySize() <= ($this->getRange() * $threshold);
    }

    public function getBuyVolume()
    {
        return $this->taker_buy_volume;
    }

    public function getSellVolume()
    {
        return $this->volume - $this->taker_buy_volume;
    }

    public function getBuySellRatio()
    {
        return $this->getSellVolume() != 0 ? $this->getBuyVolume() / $this->getSellVolume() : 0;
    }
}

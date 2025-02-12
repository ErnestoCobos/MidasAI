<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingPair extends Model
{
    protected $fillable = [
        'symbol',
        'base_asset',
        'quote_asset',
        'min_qty',
        'max_qty',
        'min_notional',
        'max_position_size',
        'maker_fee',
        'taker_fee',
        'is_active',
    ];

    protected $casts = [
        'min_qty' => 'decimal:8',
        'max_qty' => 'decimal:8',
        'min_notional' => 'decimal:8',
        'max_position_size' => 'decimal:8',
        'maker_fee' => 'decimal:4',
        'taker_fee' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function marketData()
    {
        return $this->hasMany(MarketData::class);
    }

    public function technicalIndicators()
    {
        return $this->hasMany(TechnicalIndicator::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function sentimentData()
    {
        return $this->hasMany(SentimentData::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods
    public function getFormattedSymbol()
    {
        return str_replace('/', '', $this->symbol);
    }

    public function isTrading()
    {
        return $this->positions()
            ->where('status', 'OPEN')
            ->exists();
    }

    public function getCurrentPosition()
    {
        return $this->positions()
            ->where('status', 'OPEN')
            ->first();
    }

    public function getLatestPrice()
    {
        return $this->marketData()
            ->latest('timestamp')
            ->value('close');
    }

    public function getLatestIndicators()
    {
        return $this->technicalIndicators()
            ->latest('timestamp')
            ->first();
    }

    public function getOverallSentiment()
    {
        return $this->sentimentData()
            ->latest('analyzed_at')
            ->value('sentiment_score');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'binance_order_id',
        'type',
        'side',
        'quantity',
        'price',
        'executed_qty',
        'executed_price',
        'commission',
        'commission_asset',
        'status',
        'raw_data',
        'executed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price' => 'decimal:8',
        'executed_qty' => 'decimal:8',
        'executed_price' => 'decimal:8',
        'commission' => 'decimal:8',
        'raw_data' => 'json',
        'executed_at' => 'datetime',
    ];

    // Order Types
    const TYPE_MARKET = 'MARKET';
    const TYPE_LIMIT = 'LIMIT';
    const TYPE_STOP_LOSS = 'STOP_LOSS';
    const TYPE_TAKE_PROFIT = 'TAKE_PROFIT';

    // Order Sides
    const SIDE_BUY = 'BUY';
    const SIDE_SELL = 'SELL';

    // Order Statuses
    const STATUS_NEW = 'NEW';
    const STATUS_PARTIALLY_FILLED = 'PARTIALLY_FILLED';
    const STATUS_FILLED = 'FILLED';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_EXPIRED = 'EXPIRED';

    // Relationships
    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_PARTIALLY_FILLED
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_FILLED);
    }

    public function scopeBuy($query)
    {
        return $query->where('side', self::SIDE_BUY);
    }

    public function scopeSell($query)
    {
        return $query->where('side', self::SIDE_SELL);
    }

    // Helper Methods
    public function isActive()
    {
        return in_array($this->status, [
            self::STATUS_NEW,
            self::STATUS_PARTIALLY_FILLED
        ]);
    }

    public function isFilled()
    {
        return $this->status === self::STATUS_FILLED;
    }

    public function isCanceled()
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function getRemainingQuantity()
    {
        return $this->quantity - $this->executed_qty;
    }

    public function getOrderValue()
    {
        return $this->quantity * $this->price;
    }

    public function getExecutedValue()
    {
        return $this->executed_qty * $this->executed_price;
    }

    public function getFillPercentage()
    {
        return ($this->executed_qty / $this->quantity) * 100;
    }

    public function getCommissionInUSDT()
    {
        // If commission is already in USDT
        if ($this->commission_asset === 'USDT') {
            return $this->commission;
        }

        // TODO: Implement conversion logic for other commission assets
        return 0;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'side',
        'status',
        'quantity',
        'entry_price',
        'current_price',
        'liquidation_price',
        'stop_loss',
        'take_profit',
        'trailing_stop',
        'realized_pnl',
        'unrealized_pnl',
        'commission_paid',
        'strategy_name',
        'strategy_parameters',
        'entry_signals',
        'exit_signals',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'entry_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'liquidation_price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'trailing_stop' => 'decimal:8',
        'realized_pnl' => 'decimal:8',
        'unrealized_pnl' => 'decimal:8',
        'commission_paid' => 'decimal:8',
        'strategy_parameters' => 'json',
        'entry_signals' => 'json',
        'exit_signals' => 'json',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Position Sides
    const SIDE_LONG = 'LONG';
    const SIDE_SHORT = 'SHORT';

    // Position Statuses
    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';

    // Relationships
    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeLong($query)
    {
        return $query->where('side', self::SIDE_LONG);
    }

    public function scopeShort($query)
    {
        return $query->where('side', self::SIDE_SHORT);
    }

    // Helper Methods
    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isLong()
    {
        return $this->side === self::SIDE_LONG;
    }

    public function getPositionValue()
    {
        return $this->quantity * $this->current_price;
    }

    public function getEntryValue()
    {
        return $this->quantity * $this->entry_price;
    }

    public function updateUnrealizedPnL()
    {
        if ($this->isLong()) {
            $this->unrealized_pnl = ($this->current_price - $this->entry_price) * $this->quantity;
        } else {
            $this->unrealized_pnl = ($this->entry_price - $this->current_price) * $this->quantity;
        }
        return $this->save();
    }

    public function getTotalPnL()
    {
        return $this->realized_pnl + $this->unrealized_pnl;
    }

    public function getPnLPercentage()
    {
        $entryValue = $this->getEntryValue();
        return $entryValue > 0 ? ($this->getTotalPnL() / $entryValue) * 100 : 0;
    }

    public function shouldTakeProfit()
    {
        if (!$this->take_profit) {
            return false;
        }

        return $this->isLong() 
            ? $this->current_price >= $this->take_profit
            : $this->current_price <= $this->take_profit;
    }

    public function shouldStopLoss()
    {
        if (!$this->stop_loss) {
            return false;
        }

        return $this->isLong()
            ? $this->current_price <= $this->stop_loss
            : $this->current_price >= $this->stop_loss;
    }

    public function updateTrailingStop()
    {
        if (!$this->trailing_stop || !$this->isOpen()) {
            return false;
        }

        if ($this->isLong() && $this->current_price > $this->trailing_stop) {
            $newStop = $this->current_price - ($this->current_price * 0.01); // 1% trailing stop
            if ($newStop > $this->trailing_stop) {
                $this->trailing_stop = $newStop;
                return $this->save();
            }
        } elseif (!$this->isLong() && $this->current_price < $this->trailing_stop) {
            $newStop = $this->current_price + ($this->current_price * 0.01); // 1% trailing stop
            if ($newStop < $this->trailing_stop) {
                $this->trailing_stop = $newStop;
                return $this->save();
            }
        }

        return false;
    }

    public function getDuration()
    {
        $start = $this->opened_at;
        $end = $this->closed_at ?? now();
        return $start->diffForHumans($end, true);
    }
}

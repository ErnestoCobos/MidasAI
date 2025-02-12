<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioSnapshot extends Model
{
    protected $fillable = [
        'snapshot_time',
        'total_value_usdt',
        'free_usdt',
        'locked_usdt',
        'daily_pnl',
        'daily_pnl_percentage',
        'total_pnl',
        'total_pnl_percentage',
        'daily_drawdown',
        'max_drawdown',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'profit_factor',
        'average_win',
        'average_loss',
        'asset_distribution',
        'strategy_allocation',
        'market_volatility',
        'market_trend',
    ];

    protected $casts = [
        'snapshot_time' => 'datetime',
        'total_value_usdt' => 'decimal:8',
        'free_usdt' => 'decimal:8',
        'locked_usdt' => 'decimal:8',
        'daily_pnl' => 'decimal:8',
        'daily_pnl_percentage' => 'decimal:4',
        'total_pnl' => 'decimal:8',
        'total_pnl_percentage' => 'decimal:4',
        'daily_drawdown' => 'decimal:4',
        'max_drawdown' => 'decimal:4',
        'win_rate' => 'decimal:4',
        'profit_factor' => 'decimal:4',
        'average_win' => 'decimal:8',
        'average_loss' => 'decimal:8',
        'asset_distribution' => 'json',
        'strategy_allocation' => 'json',
        'market_volatility' => 'decimal:4',
        'market_trend' => 'decimal:4',
    ];

    // Scopes
    public function scopeTimeRange($query, $start, $end)
    {
        return $query->whereBetween('snapshot_time', [$start, $end]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_time', 'desc');
    }

    public function scopeDaily($query)
    {
        return $query->whereRaw('DATE_TRUNC(\'day\', snapshot_time) = DATE_TRUNC(\'day\', NOW())');
    }

    // Helper Methods
    public function getLockedPercentage()
    {
        return $this->total_value_usdt > 0 
            ? ($this->locked_usdt / $this->total_value_usdt) * 100 
            : 0;
    }

    public function getLossRate()
    {
        return $this->total_trades > 0 
            ? ($this->losing_trades / $this->total_trades) * 100 
            : 0;
    }

    public function getRiskRewardRatio()
    {
        return $this->average_loss != 0 
            ? abs($this->average_win / $this->average_loss) 
            : 0;
    }

    public function getAssetAllocation($asset = null)
    {
        $distribution = json_decode($this->asset_distribution, true);
        return $asset ? ($distribution[$asset] ?? 0) : $distribution;
    }

    public function getStrategyPerformance($strategy = null)
    {
        $allocation = json_decode($this->strategy_allocation, true);
        return $strategy ? ($allocation[$strategy] ?? 0) : $allocation;
    }

    public function getMarketCondition()
    {
        if ($this->market_volatility > 0.5) {
            return $this->market_trend > 0 ? 'VOLATILE_BULLISH' : 'VOLATILE_BEARISH';
        }
        return $this->market_trend > 0 ? 'STABLE_BULLISH' : 'STABLE_BEARISH';
    }

    public function shouldReduceRisk()
    {
        return $this->daily_drawdown > 3 || $this->market_volatility > 0.7;
    }

    public function getPerformanceScore()
    {
        // Custom scoring based on multiple metrics
        $score = 0;
        
        // Profit factors
        $score += $this->profit_factor * 20;
        $score += $this->win_rate;
        
        // Risk factors
        $score -= $this->daily_drawdown * 10;
        $score -= $this->market_volatility * 5;
        
        return max(0, min(100, $score));
    }
}

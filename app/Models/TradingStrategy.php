<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingStrategy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'indicators',
        'parameters',
        'risk_settings',
        'entry_rules',
        'exit_rules',
        'position_sizing_rules',
        'trading_hours',
        'timeframe',
        'max_positions',
        'max_drawdown',
        'profit_target',
        'stop_loss',
        'backtest_results',
        'sharpe_ratio',
        'sortino_ratio',
        'win_rate',
        'version',
        'change_history',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'indicators' => 'json',
        'parameters' => 'json',
        'risk_settings' => 'json',
        'entry_rules' => 'json',
        'exit_rules' => 'json',
        'position_sizing_rules' => 'json',
        'trading_hours' => 'json',
        'max_positions' => 'decimal:2',
        'max_drawdown' => 'decimal:2',
        'profit_target' => 'decimal:2',
        'stop_loss' => 'decimal:2',
        'backtest_results' => 'json',
        'sharpe_ratio' => 'decimal:4',
        'sortino_ratio' => 'decimal:4',
        'win_rate' => 'decimal:2',
        'change_history' => 'json',
    ];

    // Relationships
    public function positions()
    {
        return $this->hasMany(Position::class, 'strategy_name', 'name');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTimeframe($query, $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopePerformanceAbove($query, $winRate)
    {
        return $query->where('win_rate', '>=', $winRate);
    }

    // Helper Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function getIndicator($name)
    {
        $indicators = json_decode($this->indicators, true);
        return $indicators[$name] ?? null;
    }

    public function getParameter($name)
    {
        $params = json_decode($this->parameters, true);
        return $params[$name] ?? null;
    }

    public function getRiskSetting($name)
    {
        $settings = json_decode($this->risk_settings, true);
        return $settings[$name] ?? null;
    }

    public function getBacktestMetric($name)
    {
        $results = json_decode($this->backtest_results, true);
        return $results[$name] ?? null;
    }

    public function isWithinTradingHours()
    {
        $hours = json_decode($this->trading_hours, true);
        $now = now();
        $currentDay = strtolower($now->format('l'));
        
        if (!isset($hours[$currentDay])) {
            return false;
        }

        foreach ($hours[$currentDay] as $period) {
            $start = strtotime($period['start']);
            $end = strtotime($period['end']);
            $current = strtotime($now->format('H:i'));
            
            if ($current >= $start && $current <= $end) {
                return true;
            }
        }
        
        return false;
    }

    public function canOpenNewPosition()
    {
        $openPositions = $this->positions()
            ->where('status', Position::STATUS_OPEN)
            ->count();
            
        return $openPositions < $this->max_positions;
    }

    public function getCurrentDrawdown()
    {
        $positions = $this->positions()
            ->where('status', Position::STATUS_OPEN)
            ->get();
            
        $totalPnL = $positions->sum('unrealized_pnl');
        $totalValue = $positions->sum('entry_price');
        
        return $totalValue > 0 ? ($totalPnL / $totalValue) * 100 : 0;
    }

    public function shouldPause()
    {
        return abs($this->getCurrentDrawdown()) > $this->max_drawdown;
    }

    public function logChange($type, $description)
    {
        $history = json_decode($this->change_history, true) ?? [];
        $history[] = [
            'type' => $type,
            'description' => $description,
            'timestamp' => now()->toIso8601String(),
            'version' => $this->version,
        ];
        
        $this->change_history = json_encode($history);
        return $this->save();
    }

    public function incrementVersion()
    {
        $version = explode('.', $this->version);
        $version[2] = (int)$version[2] + 1;
        $this->version = implode('.', $version);
        return $this->save();
    }
}

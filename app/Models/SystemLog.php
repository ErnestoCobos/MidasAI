<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->logged_at) {
                $model->logged_at = now();
            }
        });
    }

    protected $fillable = [
        'logged_at',
        'level',
        'component',
        'event',
        'message',
        'context',
        'trading_pair_id',
        'order_id',
        'position_id',
        'ip_address',
        'user_agent',
        'request_data',
        'system_metrics',
        'exception_class',
        'stack_trace',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'context' => 'json',
        'request_data' => 'json',
        'system_metrics' => 'json',
    ];

    // Log Levels
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_NOTICE = 'NOTICE';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    const LEVEL_ALERT = 'ALERT';
    const LEVEL_EMERGENCY = 'EMERGENCY';

    // Relationships
    public function tradingPair()
    {
        return $this->belongsTo(TradingPair::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    // Scopes
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeComponent($query, $component)
    {
        return $query->where('component', $component);
    }

    public function scopeTimeRange($query, $start, $end)
    {
        return $query->whereBetween('logged_at', [$start, $end]);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }

    public function scopeWarnings($query)
    {
        return $query->where('level', self::LEVEL_WARNING);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('logged_at', 'desc');
    }

    // Helper Methods
    public function isError()
    {
        return in_array($this->level, [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }

    public function isWarning()
    {
        return $this->level === self::LEVEL_WARNING;
    }

    public function getFormattedMessage()
    {
        return sprintf(
            '[%s] %s: %s',
            $this->level,
            $this->component,
            $this->message
        );
    }

    public function getContextValue($key)
    {
        $context = json_decode($this->context, true);
        return $context[$key] ?? null;
    }

    public function getSystemMetric($key)
    {
        $metrics = json_decode($this->system_metrics, true);
        return $metrics[$key] ?? null;
    }

    public function hasException()
    {
        return !empty($this->exception_class) && !empty($this->stack_trace);
    }

    public function getRequestInfo()
    {
        return [
            'ip' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'data' => json_decode($this->request_data, true),
        ];
    }
}

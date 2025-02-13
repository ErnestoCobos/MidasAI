<?php

namespace App\Nova\Metrics;

use App\Models\PortfolioSnapshot;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class TradingPerformance extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $snapshot = PortfolioSnapshot::latest('snapshot_time')->first();

        if (!$snapshot) {
            return $this->result([]);
        }

        return $this->result([
            'Win Rate' => $snapshot->win_rate,
            'Loss Rate' => $snapshot->getLossRate(),
            'Profit Factor' => $snapshot->profit_factor,
            'Risk/Reward' => $snapshot->getRiskRewardRatio(),
        ])->colors([
            'Win Rate' => '#22c55e',
            'Loss Rate' => '#ef4444',
            'Profit Factor' => '#3b82f6',
            'Risk/Reward' => '#eab308',
        ])->label(function ($value) {
            return match($value) {
                'Win Rate' => 'Win Rate (' . number_format($value, 2) . '%)',
                'Loss Rate' => 'Loss Rate (' . number_format($value, 2) . '%)',
                'Profit Factor' => 'Profit Factor (' . number_format($value, 2) . 'x)',
                'Risk/Reward' => 'Risk/Reward (' . number_format($value, 2) . ':1)',
                default => $value,
            };
        });
    }

    /**
     * Determine the amount of time the results should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'trading-performance';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Trading Performance';
    }
}

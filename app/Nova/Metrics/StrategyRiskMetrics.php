<?php

namespace App\Nova\Metrics;

use App\Models\TradingStrategy;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class StrategyRiskMetrics extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $strategies = TradingStrategy::all();
        $metrics = [];

        foreach ($strategies as $strategy) {
            $metrics[$strategy->name . ' (Sharpe)'] = $strategy->sharpe_ratio;
            $metrics[$strategy->name . ' (Sortino)'] = $strategy->sortino_ratio;
        }

        return $this->result($metrics)
            ->label(function ($value, $key) {
                return $key . ' (' . number_format($value, 2) . ')';
            })
            ->colors([
                '*' => function ($value) {
                    if ($value >= 2) return '#22c55e';      // green
                    if ($value >= 1) return '#84cc16';      // light green
                    if ($value >= 0) return '#eab308';      // yellow
                    return '#ef4444';                       // red
                },
            ]);
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
        return 'strategy-risk-metrics';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Strategy Risk Metrics';
    }
}

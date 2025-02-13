<?php

namespace App\Nova\Metrics;

use App\Models\TradingStrategy;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class StrategyPerformance extends Partition
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
        $performance = [];

        foreach ($strategies as $strategy) {
            $performance[$strategy->name] = $strategy->win_rate;
        }

        return $this->result($performance)
            ->label(function ($value, $key) {
                return $key . ' (' . number_format($value, 2) . '%)';
            })
            ->colors([
                '*' => function ($value) {
                    if ($value >= 60) return '#22c55e';     // green
                    if ($value >= 50) return '#84cc16';     // light green
                    if ($value >= 40) return '#eab308';     // yellow
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
        return 'strategy-performance';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Strategy Win Rates';
    }
}

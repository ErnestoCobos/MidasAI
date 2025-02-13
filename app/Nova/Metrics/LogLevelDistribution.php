<?php

namespace App\Nova\Metrics;

use App\Models\SystemLog;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class LogLevelDistribution extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, SystemLog::class, 'level')
            ->label(function ($value) {
                return $value;
            })
            ->colors([
                'DEBUG' => '#64748b',     // slate
                'INFO' => '#3b82f6',      // blue
                'NOTICE' => '#22c55e',    // green
                'WARNING' => '#eab308',   // yellow
                'ERROR' => '#ef4444',     // red
                'CRITICAL' => '#dc2626',  // darker red
                'ALERT' => '#991b1b',     // even darker red
                'EMERGENCY' => '#7f1d1d', // darkest red
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
        return 'log-level-distribution';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Log Level Distribution';
    }
}

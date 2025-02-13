<?php

namespace App\Nova\Metrics;

use App\Models\SystemLog;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

class LogEventTrend extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->countByHours($request, SystemLog::class, 'logged_at')
            ->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            6 => __('6 Hours'),
            12 => __('12 Hours'),
            24 => __('24 Hours'),
            48 => __('2 Days'),
            72 => __('3 Days'),
            'TODAY' => __('Today'),
            'MTD' => __('Month To Date'),
        ];
    }

    /**
     * Get the intervals available for the metric.
     *
     * @return array
     */
    public function intervals()
    {
        return [
            1 => __('1 Minute'),
            5 => __('5 Minutes'),
            15 => __('15 Minutes'),
            30 => __('30 Minutes'),
            60 => __('1 Hour'),
        ];
    }

    /**
     * Determine the amount of time the results should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        return now()->addMinutes(1);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'log-event-trend';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Log Events Over Time';
    }
}

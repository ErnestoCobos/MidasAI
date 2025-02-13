<?php

namespace App\Nova\Metrics;

use App\Models\PortfolioSnapshot;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

class PortfolioValueTrend extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->averageByDays(
            $request,
            PortfolioSnapshot::class,
            'total_value_usdt',
            'snapshot_time'
        )->showLatestValue()
        ->dollars();
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            7 => __('Week'),
            30 => __('30 Days'),
            60 => __('60 Days'),
            90 => __('90 Days'),
            'TODAY' => __('Today'),
            'MTD' => __('Month To Date'),
            'QTD' => __('Quarter To Date'),
            'YTD' => __('Year To Date'),
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
            1 => __('1 Hour'),
            6 => __('6 Hours'),
            12 => __('12 Hours'),
            24 => __('24 Hours'),
        ];
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
        return 'portfolio-value-trend';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Portfolio Value Trend';
    }
}

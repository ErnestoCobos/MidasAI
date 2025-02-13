<?php

namespace App\Nova\Metrics;

use App\Models\TechnicalIndicator;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

class SignalTrends extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->countByDays($request, TechnicalIndicator::where(function ($query) {
            $query->where('rsi', '>=', 70)
                ->orWhere('rsi', '<=', 30)
                ->orWhere('macd_histogram', '>', 0)
                ->orWhere('macd_histogram', '<', 0)
                ->orWhereRaw('(bb_upper - bb_lower) / bb_middle < 0.1')
                ->orWhere('volatility', '>', 0.02);
        }), 'timestamp')
            ->showLatestValue()
            ->suffix('signals');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => __('30 Days'),
            60 => __('60 Days'),
            90 => __('90 Days'),
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
        return 'signal-trends';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Signal Trends';
    }
}

<?php

namespace App\Nova\Metrics;

use App\Models\Position;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;

class ProfitLoss extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->sum($request, Position::where('status', 'CLOSED'), 'realized_pnl', 'closed_at')
            ->showLatestValue()
            ->suffix(' USDT')
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
            'TODAY' => __('Today'),
            'WEEK' => __('This Week'),
            'MTD' => __('This Month'),
            30 => __('30 Days'),
            60 => __('60 Days'),
            90 => __('90 Days'),
            'YTD' => __('This Year'),
        ];
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'profit-loss';
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
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Profit/Loss';
    }

    /**
     * Determine if the metric should be refreshed when actions run.
     *
     * @return bool
     */
    public function refreshWhenActionsRun(bool $value = true)
    {
        return $value;
    }
}

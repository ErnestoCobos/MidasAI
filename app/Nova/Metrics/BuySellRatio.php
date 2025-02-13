<?php

namespace App\Nova\Metrics;

use App\Models\MarketData;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Illuminate\Support\Facades\DB;

class BuySellRatio extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->averageByDays($request, MarketData::select(
            'timestamp',
            DB::raw('(taker_buy_volume / (volume - taker_buy_volume)) as buy_sell_ratio')
        ), 'buy_sell_ratio', 'timestamp')
            ->showLatestValue()
            ->suffix('x');
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
        return 'buy-sell-ratio';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Buy/Sell Ratio';
    }
}

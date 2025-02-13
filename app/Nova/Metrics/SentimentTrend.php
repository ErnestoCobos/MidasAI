<?php

namespace App\Nova\Metrics;

use App\Models\SentimentData;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Illuminate\Support\Facades\DB;

class SentimentTrend extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->averageByHours(
            $request,
            SentimentData::class,
            'sentiment_score',
            'analyzed_at'
        )->showLatestValue();
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
            1 => __('1 Hour'),
            3 => __('3 Hours'),
            6 => __('6 Hours'),
            12 => __('12 Hours'),
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
        return 'sentiment-trend';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Sentiment Score Trend';
    }
}

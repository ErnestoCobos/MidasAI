<?php

namespace App\Nova\Metrics;

use App\Models\SentimentData;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class SentimentDistribution extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, SentimentData::class, 'sentiment_label')
            ->label(function ($value) {
                return match($value) {
                    'VERY_POSITIVE' => 'Very Positive',
                    'POSITIVE' => 'Positive',
                    'NEUTRAL' => 'Neutral',
                    'NEGATIVE' => 'Negative',
                    'VERY_NEGATIVE' => 'Very Negative',
                    default => $value,
                };
            })
            ->colors([
                'VERY_POSITIVE' => '#28a745',   // green
                'POSITIVE' => '#98d8a0',        // light green
                'NEUTRAL' => '#6c757d',         // gray
                'NEGATIVE' => '#dc3545',        // red
                'VERY_NEGATIVE' => '#881c26',   // dark red
            ]);
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
            'TODAY' => __('Today'),
            'MTD' => __('Month To Date'),
            'QTD' => __('Quarter To Date'),
            'YTD' => __('Year To Date'),
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
        return 'sentiment-distribution';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Sentiment Distribution';
    }
}

<?php

namespace App\Nova\Metrics;

use App\Models\TechnicalIndicator;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class SignalOccurrences extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, TechnicalIndicator::class, function ($query) {
            if ($query->where('rsi', '>=', 70)->exists()) return 'Overbought';
            if ($query->where('rsi', '<=', 30)->exists()) return 'Oversold';
            if ($query->where('macd_histogram', '>', 0)->exists()) return 'Bullish MACD';
            if ($query->where('macd_histogram', '<', 0)->exists()) return 'Bearish MACD';
            if ($query->whereRaw('(bb_upper - bb_lower) / bb_middle < 0.1')->exists()) return 'BB Squeeze';
            if ($query->where('volatility', '>', 0.02)->exists()) return 'High Volatility';
            return 'Neutral';
        })->colors([
            'Overbought' => '#ef4444',
            'Oversold' => '#22c55e',
            'Bullish MACD' => '#22c55e',
            'Bearish MACD' => '#ef4444',
            'BB Squeeze' => '#eab308',
            'High Volatility' => '#3b82f6',
            'Neutral' => '#6b7280',
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
        return 'signal-occurrences';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Signal Distribution';
    }
}

<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class TechnicalSignal extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        switch ($value) {
            case 'overbought':
                return $query->where('rsi', '>=', 70);
            case 'oversold':
                return $query->where('rsi', '<=', 30);
            case 'bullish_macd':
                return $query->where('macd_histogram', '>', 0);
            case 'bearish_macd':
                return $query->where('macd_histogram', '<', 0);
            case 'bb_squeeze':
                return $query->whereRaw('(bb_upper - bb_lower) / bb_middle < 0.1');
            case 'high_volatility':
                return $query->where('volatility', '>', 0.02);
            case 'bullish_trend':
                return $query->whereRaw('ema_20 > sma_20');
            case 'bearish_trend':
                return $query->whereRaw('ema_20 < sma_20');
        }
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'RSI Overbought' => 'overbought',
            'RSI Oversold' => 'oversold',
            'Bullish MACD' => 'bullish_macd',
            'Bearish MACD' => 'bearish_macd',
            'BB Squeeze' => 'bb_squeeze',
            'High Volatility' => 'high_volatility',
            'Bullish Trend' => 'bullish_trend',
            'Bearish Trend' => 'bearish_trend',
        ];
    }

    /**
     * Get the displayable name of the filter.
     *
     * @return string
     */
    public function name()
    {
        return 'Technical Signal';
    }
}

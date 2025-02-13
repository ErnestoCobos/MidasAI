<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class TechnicalIndicator extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TechnicalIndicator>
     */
    public static $model = \App\Models\TechnicalIndicator::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'trading_pair_id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Trading Pair', 'tradingPair', TradingPair::class)
                ->sortable(),

            DateTime::make('Timestamp')
                ->sortable()
                ->filterable(),

            new Panel('RSI Indicators', [
                Number::make('RSI')
                    ->step(0.0001)
                    ->sortable(),

                Badge::make('RSI Signal')
                    ->map([
                        'overbought' => 'danger',
                        'oversold' => 'success',
                        'neutral' => 'info',
                    ])
                    ->resolveUsing(function () {
                        if ($this->isOverbought()) return 'overbought';
                        if ($this->isOversold()) return 'oversold';
                        return 'neutral';
                    }),
            ]),

            new Panel('MACD Indicators', [
                Number::make('MACD Line', 'macd_line')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('MACD Signal', 'macd_signal')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('MACD Histogram', 'macd_histogram')
                    ->step(0.00000001)
                    ->sortable(),

                Badge::make('MACD Signal')
                    ->map([
                        'bullish' => 'success',
                        'bearish' => 'danger',
                        'neutral' => 'info',
                    ])
                    ->resolveUsing(function () {
                        if ($this->isBullishMACD()) return 'bullish';
                        if ($this->isBearishMACD()) return 'bearish';
                        return 'neutral';
                    }),
            ]),

            new Panel('Bollinger Bands', [
                Number::make('BB Upper', 'bb_upper')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('BB Middle', 'bb_middle')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('BB Lower', 'bb_lower')
                    ->step(0.00000001)
                    ->sortable(),

                Badge::make('BB Width')
                    ->map([
                        'squeeze' => 'warning',
                        'normal' => 'info',
                        'wide' => 'success',
                    ])
                    ->resolveUsing(function () {
                        if ($this->isBBSqueeze()) return 'squeeze';
                        if ($this->getBBWidth() > 0.2) return 'wide';
                        return 'normal';
                    }),
            ]),

            new Panel('Moving Averages', [
                Number::make('SMA 20', 'sma_20')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('EMA 20', 'ema_20')
                    ->step(0.00000001)
                    ->sortable(),

                Badge::make('Trend')
                    ->map([
                        'strong_bullish' => 'success',
                        'bullish' => 'info',
                        'bearish' => 'warning',
                        'strong_bearish' => 'danger',
                    ])
                    ->resolveUsing(function () {
                        $strength = $this->getTrendStrength();
                        if ($strength > 1) return 'strong_bullish';
                        if ($strength > 0) return 'bullish';
                        if ($strength > -1) return 'bearish';
                        return 'strong_bearish';
                    }),
            ]),

            new Panel('Volatility Metrics', [
                Number::make('ATR')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Volatility')
                    ->step(0.0001)
                    ->sortable()
                    ->displayUsing(fn ($value) => number_format($value * 100, 2) . '%'),
            ]),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [
            (new Metrics\SignalOccurrences)->width('2/3'),
            (new Metrics\SignalTrends)->width('1/3'),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new Filters\TechnicalSignal,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [
            new Lenses\SignificantSignals,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}

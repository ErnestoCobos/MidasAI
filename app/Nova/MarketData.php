<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class MarketData extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\MarketData>
     */
    public static $model = \App\Models\MarketData::class;

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

            new Panel('Price Information', [
                Number::make('Open')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('High')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Low')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Close')
                    ->step(0.00000001)
                    ->sortable(),

                Badge::make('Candle Type')
                    ->map([
                        'bullish' => 'success',
                        'bearish' => 'danger',
                        'doji' => 'warning',
                    ])
                    ->resolveUsing(function ($resource) {
                        if ($resource->isDoji()) return 'doji';
                        return $resource->isBullish() ? 'bullish' : 'bearish';
                    }),

                Number::make('Range')
                    ->resolveUsing(fn ($resource) => $resource->getRange())
                    ->step(0.00000001)
                    ->sortable(),
            ]),

            new Panel('Volume Analysis', [
                Number::make('Volume')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Quote Volume')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Number of Trades')
                    ->sortable(),

                Number::make('Taker Buy Volume')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Taker Buy Quote Volume')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Buy/Sell Ratio')
                    ->resolveUsing(fn ($resource) => $resource->getBuySellRatio())
                    ->displayUsing(fn ($value) => number_format($value, 2) . 'x')
                    ->sortable(),

                Badge::make('Volume Type')
                    ->map([
                        'high_buy' => 'success',
                        'high_sell' => 'danger',
                        'neutral' => 'info',
                    ])
                    ->resolveUsing(function ($resource) {
                        $ratio = $resource->getBuySellRatio();
                        if ($ratio > 1.5) return 'high_buy';
                        if ($ratio < 0.5) return 'high_sell';
                        return 'neutral';
                    }),
            ]),

            new Panel('Price Action Analysis', [
                Number::make('Body Size')
                    ->resolveUsing(fn ($resource) => $resource->getBodySize())
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Upper Shadow')
                    ->resolveUsing(fn ($resource) => $resource->getUpperShadow())
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Lower Shadow')
                    ->resolveUsing(fn ($resource) => $resource->getLowerShadow())
                    ->step(0.00000001)
                    ->sortable(),

                Badge::make('Pattern')
                    ->map([
                        'doji' => 'warning',
                        'hammer' => 'success',
                        'shooting_star' => 'danger',
                        'marubozu' => 'info',
                    ])
                    ->resolveUsing(function ($resource) {
                        if ($resource->isDoji()) return 'doji';
                        if ($resource->getLowerShadow() > ($resource->getRange() * 0.6)) return 'hammer';
                        if ($resource->getUpperShadow() > ($resource->getRange() * 0.6)) return 'shooting_star';
                        if ($resource->getBodySize() > ($resource->getRange() * 0.8)) return 'marubozu';
                        return null;
                    }),
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
            (new Metrics\PriceVolatility)->width('1/2'),
            (new Metrics\BuySellRatio)->width('1/2'),
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
        return [];
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
            new Lenses\PriceActionPatterns,
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

<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class Position extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Position>
     */
    public static $model = \App\Models\Position::class;

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
        'strategy_name',
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

            new Panel('Position Information', [
                BelongsTo::make('Trading Pair', 'tradingPair')
                    ->sortable(),

                Badge::make('Side')
                    ->map([
                        'LONG' => 'success',
                        'SHORT' => 'danger',
                    ]),

                Badge::make('Status')
                    ->map([
                        'OPEN' => 'success',
                        'CLOSED' => 'info',
                    ]),

                Text::make('Strategy', 'strategy_name')
                    ->sortable(),
            ]),

            new Panel('Position Details', [
                Number::make('Quantity')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Entry Price')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Current Price')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Position Value')
                    ->resolveUsing(function ($resource) {
                        return $resource->getPositionValue();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),
            ]),

            new Panel('Risk Management', [
                Number::make('Stop Loss')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Take Profit')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Trailing Stop')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Liquidation Price')
                    ->step(0.00000001)
                    ->sortable(),
            ]),

            new Panel('Performance', [
                Number::make('Realized PnL')
                    ->step(0.00000001)
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Unrealized PnL')
                    ->step(0.00000001)
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Total PnL')
                    ->resolveUsing(function ($resource) {
                        return $resource->getTotalPnL();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('PnL %')
                    ->resolveUsing(function ($resource) {
                        return $resource->getPnLPercentage();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Commission Paid')
                    ->step(0.00000001)
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),
            ]),

            new Panel('Timestamps', [
                DateTime::make('Opened At')
                    ->sortable(),

                DateTime::make('Closed At')
                    ->sortable()
                    ->nullable(),

                Text::make('Duration')
                    ->resolveUsing(function ($resource) {
                        return $resource->getDuration();
                    }),
            ]),

            new Panel('Strategy Details', [
                Code::make('Strategy Parameters')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Entry Signals')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Exit Signals')
                    ->json()
                    ->onlyOnDetail(),
            ]),

            HasMany::make('Orders'),
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
            (new Metrics\PositionPerformance)->width('1/2'),
            (new Metrics\PositionPnLTrend)->width('1/2'),
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
            new Filters\PositionFilter,
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
            new Lenses\ActivePositions,
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

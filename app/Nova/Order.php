<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class Order extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Order>
     */
    public static $model = \App\Models\Order::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'binance_order_id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'binance_order_id',
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

            new Panel('Order Information', [
                BelongsTo::make('Trading Pair', 'tradingPair')
                    ->sortable(),

                Text::make('Binance Order ID', 'binance_order_id')
                    ->sortable(),

                Badge::make('Type')
                    ->map([
                        'MARKET' => 'info',
                        'LIMIT' => 'success',
                        'STOP_LOSS' => 'warning',
                        'TAKE_PROFIT' => 'warning',
                    ]),

                Badge::make('Side')
                    ->map([
                        'BUY' => 'success',
                        'SELL' => 'danger',
                    ]),

                Badge::make('Status')
                    ->map([
                        'NEW' => 'info',
                        'PARTIALLY_FILLED' => 'warning',
                        'FILLED' => 'success',
                        'CANCELED' => 'danger',
                        'REJECTED' => 'danger',
                        'EXPIRED' => 'info',
                    ])
                    ->sortable(),
            ]),

            new Panel('Order Details', [
                Number::make('Quantity')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Price')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Order Value')
                    ->resolveUsing(function ($resource) {
                        return $resource->getOrderValue();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),
            ]),

            new Panel('Execution Details', [
                Number::make('Executed Quantity', 'executed_qty')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Executed Price', 'executed_price')
                    ->step(0.00000001)
                    ->sortable(),

                Number::make('Executed Value')
                    ->resolveUsing(function ($resource) {
                        return $resource->getExecutedValue();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Fill %')
                    ->resolveUsing(function ($resource) {
                        return $resource->getFillPercentage();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),
            ]),

            new Panel('Commission Details', [
                Number::make('Commission')
                    ->step(0.00000001)
                    ->sortable(),

                Text::make('Commission Asset')
                    ->sortable(),

                Number::make('Commission in USDT')
                    ->resolveUsing(function ($resource) {
                        return $resource->getCommissionInUSDT();
                    })
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),
            ]),

            new Panel('Timestamps', [
                DateTime::make('Created At', 'created_at')
                    ->sortable(),

                DateTime::make('Updated At', 'updated_at')
                    ->sortable(),

                DateTime::make('Executed At')
                    ->sortable(),
            ]),

            new Panel('Raw Data', [
                Code::make('Raw Data', 'raw_data')
                    ->json()
                    ->onlyOnDetail(),
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
            (new Metrics\OrderExecutionStats)->width('1/2'),
            (new Metrics\OrderValueTrend)->width('1/2'),
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
            new Filters\OrderFilter,
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
            new Lenses\ActiveOrders,
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

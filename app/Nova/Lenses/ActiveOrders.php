<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class ActiveOrders extends Lens
{
    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        return $request->withOrdering($request->withFilters(
            $query->whereIn('status', ['NEW', 'PARTIALLY_FILLED'])
                ->orderBy('created_at', 'desc')
        ));
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

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

            Number::make('Quantity')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Price')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Executed Quantity', 'executed_qty')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Executed Price', 'executed_price')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Fill %')
                ->resolveUsing(function ($resource) {
                    return $resource->getFillPercentage();
                })
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Order Value')
                ->resolveUsing(function ($resource) {
                    return $resource->getOrderValue();
                })
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Executed Value')
                ->resolveUsing(function ($resource) {
                    return $resource->getExecutedValue();
                })
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Badge::make('Status')
                ->map([
                    'NEW' => 'info',
                    'PARTIALLY_FILLED' => 'warning',
                ])
                ->sortable(),

            DateTime::make('Created At', 'created_at')
                ->sortable(),

            DateTime::make('Last Update', 'updated_at')
                ->sortable(),
        ];
    }

    /**
     * Get the cards available on the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return parent::actions($request);
    }

    /**
     * Get the URI key for the lens.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'active-orders';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Active Orders';
    }
}

<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class ActivePositions extends Lens
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
            $query->where('status', 'OPEN')
                ->orderBy('opened_at', 'desc')
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

            Badge::make('Side')
                ->map([
                    'LONG' => 'success',
                    'SHORT' => 'danger',
                ]),

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

            Number::make('Unrealized PnL')
                ->resolveUsing(function ($resource) {
                    return $resource->unrealized_pnl;
                })
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('PnL %')
                ->resolveUsing(function ($resource) {
                    return $resource->getPnLPercentage();
                })
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Stop Loss')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Take Profit')
                ->step(0.00000001)
                ->sortable(),

            Number::make('Trailing Stop')
                ->step(0.00000001)
                ->sortable(),

            Text::make('Strategy', 'strategy_name')
                ->sortable(),

            Text::make('Duration')
                ->resolveUsing(function ($resource) {
                    return $resource->getDuration();
                }),

            DateTime::make('Opened At', 'opened_at')
                ->sortable(),

            Code::make('Strategy Parameters', 'strategy_parameters')
                ->json()
                ->onlyOnDetail(),

            Code::make('Entry Signals', 'entry_signals')
                ->json()
                ->onlyOnDetail(),
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
        return 'active-positions';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Active Positions';
    }
}

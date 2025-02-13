<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class ActiveTradingPairs extends Lens
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
            $query->where('is_active', true)
                ->withCount(['positions' => function (Builder $query) {
                    $query->where('status', 'OPEN');
                }])
                ->withSum(['positions as total_pnl' => function (Builder $query) {
                    $query->where('status', 'CLOSED');
                }], 'realized_pnl')
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

            Text::make('Symbol')
                ->sortable(),

            Text::make('Base Asset')
                ->sortable(),

            Text::make('Quote Asset')
                ->sortable(),

            Number::make('Open Positions', 'positions_count')
                ->sortable(),

            Number::make('Total P/L', 'total_pnl')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Badge::make('Status')
                ->map([
                    'trading' => 'success',
                    'idle' => 'info',
                ])
                ->resolveUsing(function ($resource) {
                    return $resource->positions_count > 0 ? 'trading' : 'idle';
                }),
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
        return 'active-trading-pairs';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Active Trading Pairs';
    }
}

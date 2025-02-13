<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class PairsWithOpenPositions extends Lens
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
            $query->whereHas('positions', function (Builder $query) {
                $query->where('status', 'OPEN');
            })
            ->withCount(['positions' => function (Builder $query) {
                $query->where('status', 'OPEN');
            }])
            ->withSum(['positions as total_position_size' => function (Builder $query) {
                $query->where('status', 'OPEN');
            }], 'size')
            ->withSum(['positions as unrealized_pnl' => function (Builder $query) {
                $query->where('status', 'OPEN');
            }], 'unrealized_pnl')
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

            Number::make('Open Positions', 'positions_count')
                ->sortable(),

            Currency::make('Position Size', 'total_position_size')
                ->currency('USD')
                ->sortable(),

            Currency::make('Unrealized P/L', 'unrealized_pnl')
                ->currency('USD')
                ->sortable(),

            Number::make('Average Entry', function () {
                if ($this->total_position_size > 0) {
                    return $this->positions()
                        ->where('status', 'OPEN')
                        ->avg('entry_price');
                }
                return 0;
            })->displayUsing(fn ($value) => number_format($value, 2))
                ->sortable(),

            Number::make('Current Price', function () {
                return $this->getLatestPrice();
            })->displayUsing(fn ($value) => number_format($value, 2))
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
        return 'pairs-with-open-positions';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Pairs With Open Positions';
    }
}

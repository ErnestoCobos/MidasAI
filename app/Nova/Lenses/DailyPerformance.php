<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class DailyPerformance extends Lens
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
            $query->whereRaw('DATE(snapshot_time) = DATE(snapshot_time)')
                ->orderBy('snapshot_time', 'desc')
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

            DateTime::make('Snapshot Time')
                ->sortable(),

            Number::make('Total Value', 'total_value_usdt')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Free USDT', 'free_usdt')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Locked USDT', 'locked_usdt')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Daily PnL', 'daily_pnl')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Daily PnL %', 'daily_pnl_percentage')
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Total PnL', 'total_pnl')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Total PnL %', 'total_pnl_percentage')
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Daily Drawdown', 'daily_drawdown')
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Max Drawdown', 'max_drawdown')
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Win Rate', 'win_rate')
                ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                ->sortable(),

            Number::make('Profit Factor')
                ->displayUsing(fn ($value) => number_format($value, 2) . 'x')
                ->sortable(),

            Number::make('Average Win')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Number::make('Average Loss')
                ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                ->sortable(),

            Badge::make('Market Condition')
                ->resolveUsing(fn ($resource) => $resource->getMarketCondition())
                ->map([
                    'VOLATILE_BULLISH' => 'warning',
                    'VOLATILE_BEARISH' => 'danger',
                    'STABLE_BULLISH' => 'success',
                    'STABLE_BEARISH' => 'info',
                ]),

            Number::make('Performance Score')
                ->resolveUsing(fn ($resource) => $resource->getPerformanceScore())
                ->displayUsing(fn ($value) => number_format($value, 0) . '/100')
                ->sortable(),

            Code::make('Asset Distribution')
                ->json()
                ->onlyOnDetail(),

            Code::make('Strategy Allocation')
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
        return 'daily-performance';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Daily Performance';
    }
}

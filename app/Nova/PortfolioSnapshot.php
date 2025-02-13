<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class PortfolioSnapshot extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\PortfolioSnapshot>
     */
    public static $model = \App\Models\PortfolioSnapshot::class;

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

            DateTime::make('Snapshot Time')
                ->sortable(),

            new Panel('Portfolio Value', [
                Number::make('Total Value', 'total_value_usdt')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Free USDT')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Locked USDT')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Locked %')
                    ->resolveUsing(fn ($resource) => $resource->getLockedPercentage())
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),
            ]),

            new Panel('Performance Metrics', [
                Number::make('Daily PnL')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Daily PnL %')
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Total PnL')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Total PnL %')
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),
            ]),

            new Panel('Risk Metrics', [
                Number::make('Daily Drawdown')
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Max Drawdown')
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Market Volatility')
                    ->displayUsing(fn ($value) => number_format($value * 100, 2) . '%')
                    ->sortable(),

                Number::make('Market Trend')
                    ->displayUsing(fn ($value) => number_format($value, 4))
                    ->sortable(),

                Badge::make('Market Condition')
                    ->resolveUsing(fn ($resource) => $resource->getMarketCondition())
                    ->map([
                        'VOLATILE_BULLISH' => 'warning',
                        'VOLATILE_BEARISH' => 'danger',
                        'STABLE_BULLISH' => 'success',
                        'STABLE_BEARISH' => 'info',
                    ]),

                Badge::make('Risk Level')
                    ->resolveUsing(fn ($resource) => $resource->shouldReduceRisk() ? 'HIGH' : 'NORMAL')
                    ->map([
                        'HIGH' => 'danger',
                        'NORMAL' => 'success',
                    ]),
            ]),

            new Panel('Trading Statistics', [
                Number::make('Total Trades')
                    ->sortable(),

                Number::make('Winning Trades')
                    ->sortable(),

                Number::make('Losing Trades')
                    ->sortable(),

                Number::make('Win Rate')
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Loss Rate')
                    ->resolveUsing(fn ($resource) => $resource->getLossRate())
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Profit Factor')
                    ->displayUsing(fn ($value) => number_format($value, 2) . 'x')
                    ->sortable(),

                Number::make('Risk/Reward Ratio')
                    ->resolveUsing(fn ($resource) => $resource->getRiskRewardRatio())
                    ->displayUsing(fn ($value) => number_format($value, 2) . ':1')
                    ->sortable(),

                Number::make('Average Win')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Average Loss')
                    ->displayUsing(fn ($value) => number_format($value, 2) . ' USDT')
                    ->sortable(),

                Number::make('Performance Score')
                    ->resolveUsing(fn ($resource) => $resource->getPerformanceScore())
                    ->displayUsing(fn ($value) => number_format($value, 0) . '/100')
                    ->sortable(),
            ]),

            new Panel('Portfolio Distribution', [
                Code::make('Asset Distribution')
                    ->json(),

                Code::make('Strategy Allocation')
                    ->json(),
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
            (new Metrics\PortfolioValueTrend)->width('1/2'),
            (new Metrics\TradingPerformance)->width('1/2'),
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
            new Lenses\DailyPerformance,
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

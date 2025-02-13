<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class TradingStrategy extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TradingStrategy>
     */
    public static $model = \App\Models\TradingStrategy::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
        'description',
        'timeframe',
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

            new Panel('Strategy Information', [
                Text::make('Name')
                    ->sortable()
                    ->rules('required', 'max:255'),

                Textarea::make('Description')
                    ->rules('required'),

                Boolean::make('Is Active')
                    ->sortable(),

                Text::make('Version')
                    ->sortable()
                    ->readonly(),

                Text::make('Timeframe')
                    ->sortable(),
            ]),

            new Panel('Risk Parameters', [
                Number::make('Max Positions')
                    ->min(1)
                    ->step(1)
                    ->sortable(),

                Number::make('Max Drawdown', 'max_drawdown')
                    ->help('Maximum allowed drawdown percentage')
                    ->step(0.01)
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Profit Target')
                    ->step(0.01)
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Stop Loss')
                    ->step(0.01)
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),
            ]),

            new Panel('Performance Metrics', [
                Number::make('Win Rate')
                    ->step(0.01)
                    ->displayUsing(fn ($value) => number_format($value, 2) . '%')
                    ->sortable(),

                Number::make('Sharpe Ratio')
                    ->step(0.0001)
                    ->sortable(),

                Number::make('Sortino Ratio')
                    ->step(0.0001)
                    ->sortable(),

                Badge::make('Risk Level')
                    ->resolveUsing(function ($resource) {
                        if ($resource->sharpe_ratio >= 2) return 'LOW';
                        if ($resource->sharpe_ratio >= 1) return 'MEDIUM';
                        return 'HIGH';
                    })
                    ->map([
                        'LOW' => 'success',
                        'MEDIUM' => 'warning',
                        'HIGH' => 'danger',
                    ]),
            ]),

            new Panel('Strategy Configuration', [
                Code::make('Indicators')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Parameters')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Risk Settings', 'risk_settings')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Entry Rules', 'entry_rules')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Exit Rules', 'exit_rules')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Position Sizing Rules', 'position_sizing_rules')
                    ->json()
                    ->rules('required', 'json'),

                Code::make('Trading Hours', 'trading_hours')
                    ->json()
                    ->rules('required', 'json'),
            ]),

            new Panel('Backtest Results', [
                Code::make('Backtest Results', 'backtest_results')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Change History', 'change_history')
                    ->json()
                    ->onlyOnDetail(),
            ]),

            HasMany::make('Positions', 'positions', Position::class),
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
            (new Metrics\StrategyPerformance)->width('1/2'),
            (new Metrics\StrategyRiskMetrics)->width('1/2'),
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
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            (new Actions\ActivateStrategy)
                ->confirmButtonText('Activate')
                ->showOnTableRow()
                ->canSee(function ($request) {
                    return true;
                }),

            (new Actions\DeactivateStrategy)
                ->confirmButtonText('Deactivate')
                ->showOnTableRow()
                ->canSee(function ($request) {
                    return true;
                }),
        ];
    }
}

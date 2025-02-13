<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class SystemLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\SystemLog>
     */
    public static $model = \App\Models\SystemLog::class;

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
        'component',
        'event',
        'message',
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

            DateTime::make('Logged At')
                ->sortable(),

            new Panel('Log Details', [
                Badge::make('Level')
                    ->map([
                        'DEBUG' => 'info',
                        'INFO' => 'success',
                        'NOTICE' => 'success',
                        'WARNING' => 'warning',
                        'ERROR' => 'danger',
                        'CRITICAL' => 'danger',
                        'ALERT' => 'danger',
                        'EMERGENCY' => 'danger',
                    ]),

                Text::make('Component')
                    ->sortable(),

                Text::make('Event')
                    ->sortable(),

                Text::make('Message')
                    ->sortable(),
            ]),

            new Panel('Related Entities', [
                BelongsTo::make('Trading Pair', 'tradingPair')
                    ->nullable(),

                BelongsTo::make('Order')
                    ->nullable(),

                BelongsTo::make('Position')
                    ->nullable(),
            ]),

            new Panel('Request Information', [
                Text::make('IP Address')
                    ->onlyOnDetail(),

                Text::make('User Agent')
                    ->onlyOnDetail(),

                Code::make('Request Data')
                    ->json()
                    ->onlyOnDetail(),
            ]),

            new Panel('System Information', [
                Code::make('System Metrics')
                    ->json()
                    ->onlyOnDetail(),

                Text::make('Exception Class')
                    ->onlyOnDetail(),

                Code::make('Stack Trace')
                    ->onlyOnDetail(),
            ]),

            new Panel('Additional Context', [
                Code::make('Context')
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
            (new Metrics\LogLevelDistribution)->width('1/2'),
            (new Metrics\LogEventTrend)->width('1/2'),
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
            new Filters\LogFilter,
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
        return [];
    }
}

<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class SentimentData extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\SentimentData>
     */
    public static $model = \App\Models\SentimentData::class;

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
        'source_url',
        'source_author',
        'content',
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

            new Panel('Basic Information', [
                BelongsTo::make('Trading Pair', 'tradingPair')
                    ->sortable(),

                DateTime::make('Analyzed At')
                    ->sortable(),

                Badge::make('Source Type')
                    ->map([
                        'NEWS' => 'info',
                        'TWITTER' => 'primary',
                        'REDDIT' => 'warning',
                        'TELEGRAM' => 'success',
                        'OTHER' => 'default',
                    ]),

                Text::make('Source URL')
                    ->hideFromIndex(),

                Text::make('Source Author')
                    ->hideFromIndex(),

                Text::make('Language')
                    ->hideFromIndex(),

                Number::make('Reach')
                    ->sortable(),
            ]),

            new Panel('Content', [
                Textarea::make('Content')
                    ->alwaysShow(),
            ]),

            new Panel('Sentiment Analysis', [
                Number::make('Sentiment Score')
                    ->step(0.0001)
                    ->sortable(),

                Badge::make('Sentiment Label')
                    ->map([
                        'VERY_POSITIVE' => 'success',
                        'POSITIVE' => 'info',
                        'NEUTRAL' => 'default',
                        'NEGATIVE' => 'warning',
                        'VERY_NEGATIVE' => 'danger',
                    ]),

                Number::make('Confidence Score')
                    ->step(0.0001)
                    ->sortable(),

                Number::make('Impact Score')
                    ->step(0.0001)
                    ->sortable(),
            ]),

            new Panel('Analysis Details', [
                Code::make('Entity Mentions')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Topic Classification')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Keyword Extraction')
                    ->json()
                    ->onlyOnDetail(),

                Code::make('Raw Analysis Data')
                    ->json()
                    ->onlyOnDetail(),
            ]),

            new Panel('Technical Details', [
                Text::make('Analyzer Version')
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
            (new Metrics\SentimentDistribution)->width('1/2'),
            (new Metrics\SentimentTrend)->width('1/2'),
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
            new Filters\SentimentFilter,
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

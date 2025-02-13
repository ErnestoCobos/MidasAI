<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\Trend;

class TradingPair extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\TradingPair>
     */
    public static $model = \App\Models\TradingPair::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'symbol';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'symbol',
        'base_asset',
        'quote_asset',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Symbol')
                ->sortable()
                ->rules('required', 'max:20'),

            Text::make('Base Asset')
                ->sortable()
                ->rules('required', 'max:10'),

            Text::make('Quote Asset')
                ->sortable()
                ->rules('required', 'max:10'),

            Number::make('Min Qty')
                ->step(0.00000001)
                ->rules('required', 'numeric', 'min:0'),

            Number::make('Max Qty')
                ->step(0.00000001)
                ->rules('required', 'numeric', 'min:0'),

            Number::make('Min Notional')
                ->step(0.00000001)
                ->rules('required', 'numeric', 'min:0'),

            Number::make('Max Position Size')
                ->step(0.00000001)
                ->rules('required', 'numeric', 'min:0'),

            Number::make('Maker Fee')
                ->step(0.0001)
                ->rules('required', 'numeric', 'min:0', 'max:1')
                ->displayUsing(fn ($value) => number_format($value * 100, 2) . '%'),

            Number::make('Taker Fee')
                ->step(0.0001)
                ->rules('required', 'numeric', 'min:0', 'max:1')
                ->displayUsing(fn ($value) => number_format($value * 100, 2) . '%'),

            Boolean::make('Is Active')
                ->sortable(),

            Badge::make('Status')
                ->map([
                    'active' => 'success',
                    'inactive' => 'danger',
                ])
                ->resolveUsing(function () {
                    return $this->is_active ? 'active' : 'inactive';
                }),

            HasMany::make('Market Data', 'marketData', MarketData::class),
            HasMany::make('Technical Indicators', 'technicalIndicators', TechnicalIndicator::class),
            HasMany::make('Orders', 'orders', Order::class),
            HasMany::make('Positions', 'positions', Position::class),
            HasMany::make('Sentiment Data', 'sentimentData', SentimentData::class),
        ];
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [
            (new Metrics\TradingVolume)->width('1/3'),
            (new Metrics\ActivePositions)->width('1/3'),
            (new Metrics\ProfitLoss)->width('1/3'),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [
            new Filters\ActivePairs,
            new Filters\QuoteAsset,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [
            new Lenses\ActiveTradingPairs,
            new Lenses\PairsWithOpenPositions,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [
            (new Actions\ActivatePairs)
                ->confirmText('Are you sure you want to activate these trading pairs?')
                ->confirmButtonText('Activate')
                ->cancelButtonText('Cancel'),

            (new Actions\DeactivatePairs)
                ->confirmText('Are you sure you want to deactivate these trading pairs?')
                ->confirmButtonText('Deactivate')
                ->cancelButtonText('Cancel')
                ->destructive(),
        ];
    }
}

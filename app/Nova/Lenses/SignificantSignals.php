<?php

namespace App\Nova\Lenses;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Http\Requests\LensRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;
use Illuminate\Database\Eloquent\Builder;

class SignificantSignals extends Lens
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
            $query->where(function ($query) {
                $query->where('rsi', '>=', 70)
                    ->orWhere('rsi', '<=', 30)
                    ->orWhere('macd_histogram', '>', 0)
                    ->orWhere('macd_histogram', '<', 0)
                    ->orWhereRaw('(bb_upper - bb_lower) / bb_middle < 0.1')
                    ->orWhere('volatility', '>', 0.02);
            })
            ->orderBy('timestamp', 'desc')
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

            DateTime::make('Timestamp')
                ->sortable(),

            Badge::make('Signal Type')
                ->map([
                    'overbought' => 'danger',
                    'oversold' => 'success',
                    'bullish_macd' => 'success',
                    'bearish_macd' => 'danger',
                    'bb_squeeze' => 'warning',
                    'high_volatility' => 'info',
                ])
                ->resolveUsing(function ($resource) {
                    if ($resource->rsi >= 70) return 'overbought';
                    if ($resource->rsi <= 30) return 'oversold';
                    if ($resource->macd_histogram > 0) return 'bullish_macd';
                    if ($resource->macd_histogram < 0) return 'bearish_macd';
                    if ($resource->getBBWidth() < 0.1) return 'bb_squeeze';
                    if ($resource->volatility > 0.02) return 'high_volatility';
                    return null;
                }),

            Number::make('RSI')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 2)),

            Number::make('MACD Histogram', 'macd_histogram')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 8)),

            Number::make('BB Width')
                ->resolveUsing(function ($resource) {
                    return $resource->getBBWidth();
                })
                ->displayUsing(fn ($value) => number_format($value, 4))
                ->sortable(),

            Number::make('Volatility')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value * 100, 2) . '%'),

            Badge::make('Trend')
                ->map([
                    'bullish' => 'success',
                    'bearish' => 'danger',
                ])
                ->resolveUsing(function ($resource) {
                    return $resource->ema_20 > $resource->sma_20 ? 'bullish' : 'bearish';
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
        return 'significant-signals';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Significant Signals';
    }
}

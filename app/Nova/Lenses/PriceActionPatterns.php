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
use Illuminate\Support\Facades\DB;

class PriceActionPatterns extends Lens
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
            $query->select([
                'market_data.*',
                DB::raw('ABS(close - open) as body_size'),
                DB::raw('high - GREATEST(open, close) as upper_shadow'),
                DB::raw('LEAST(open, close) - low as lower_shadow'),
                DB::raw('(high - low) as range'),
                DB::raw('taker_buy_volume / (volume - taker_buy_volume) as buy_sell_ratio'),
            ])
            ->where(function ($query) {
                // Doji pattern
                $query->whereRaw('ABS(close - open) <= ((high - low) * 0.1)')
                // Pin bar pattern (long shadow)
                ->orWhereRaw('(high - GREATEST(open, close)) >= ((high - low) * 0.6)')
                ->orWhereRaw('(LEAST(open, close) - low) >= ((high - low) * 0.6)')
                // Strong momentum (large body)
                ->orWhereRaw('ABS(close - open) >= ((high - low) * 0.7)')
                // High volume
                ->orWhereRaw('volume >= (SELECT AVG(volume) * 2 FROM market_data)');
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

            Badge::make('Pattern')
                ->map([
                    'doji' => 'warning',
                    'pin_bar_up' => 'success',
                    'pin_bar_down' => 'danger',
                    'strong_bullish' => 'success',
                    'strong_bearish' => 'danger',
                    'high_volume' => 'info',
                ])
                ->resolveUsing(function ($resource) {
                    $bodySize = abs($resource->close - $resource->open);
                    $range = $resource->high - $resource->low;
                    
                    if ($bodySize <= ($range * 0.1)) return 'doji';
                    if ($resource->upper_shadow >= ($range * 0.6)) return 'pin_bar_down';
                    if ($resource->lower_shadow >= ($range * 0.6)) return 'pin_bar_up';
                    if ($bodySize >= ($range * 0.7)) {
                        return $resource->close > $resource->open ? 'strong_bullish' : 'strong_bearish';
                    }
                    return 'high_volume';
                }),

            Number::make('Open')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 8)),

            Number::make('High')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 8)),

            Number::make('Low')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 8)),

            Number::make('Close')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 8)),

            Number::make('Volume')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 2)),

            Number::make('Buy/Sell Ratio', 'buy_sell_ratio')
                ->sortable()
                ->displayUsing(fn ($value) => number_format($value, 2) . 'x'),

            Badge::make('Trend')
                ->map([
                    'bullish' => 'success',
                    'bearish' => 'danger',
                ])
                ->resolveUsing(fn ($resource) => $resource->close > $resource->open ? 'bullish' : 'bearish'),
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
        return 'price-action-patterns';
    }

    /**
     * Get the displayable name of the lens.
     *
     * @return string
     */
    public function name()
    {
        return 'Price Action Patterns';
    }
}

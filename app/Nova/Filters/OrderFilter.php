<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\Order;

class OrderFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Order Filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        $parts = explode(':', $value);
        $category = $parts[0];
        $value = $parts[1];

        return match($category) {
            'type' => $query->where('type', $value),
            'side' => $query->where('side', $value),
            'status' => $query->where('status', $value),
            default => $query,
        };
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'Order Types' => [
                'Market' => 'type:' . Order::TYPE_MARKET,
                'Limit' => 'type:' . Order::TYPE_LIMIT,
                'Stop Loss' => 'type:' . Order::TYPE_STOP_LOSS,
                'Take Profit' => 'type:' . Order::TYPE_TAKE_PROFIT,
            ],
            'Order Sides' => [
                'Buy' => 'side:' . Order::SIDE_BUY,
                'Sell' => 'side:' . Order::SIDE_SELL,
            ],
            'Order Status' => [
                'New' => 'status:' . Order::STATUS_NEW,
                'Partially Filled' => 'status:' . Order::STATUS_PARTIALLY_FILLED,
                'Filled' => 'status:' . Order::STATUS_FILLED,
                'Canceled' => 'status:' . Order::STATUS_CANCELED,
                'Rejected' => 'status:' . Order::STATUS_REJECTED,
                'Expired' => 'status:' . Order::STATUS_EXPIRED,
            ],
        ];
    }

    /**
     * The default value of the filter.
     *
     * @return string|null
     */
    public function default()
    {
        return null;
    }
}

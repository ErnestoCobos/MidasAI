<?php

namespace App\Nova\Metrics;

use App\Models\Order;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class OrderExecutionStats extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, Order::class, 'status')
            ->label(function ($value) {
                return match($value) {
                    'NEW' => 'New',
                    'PARTIALLY_FILLED' => 'Partially Filled',
                    'FILLED' => 'Filled',
                    'CANCELED' => 'Canceled',
                    'REJECTED' => 'Rejected',
                    'EXPIRED' => 'Expired',
                    default => $value,
                };
            })
            ->colors([
                'NEW' => '#3b82f6', // blue
                'PARTIALLY_FILLED' => '#eab308', // yellow
                'FILLED' => '#22c55e', // green
                'CANCELED' => '#ef4444', // red
                'REJECTED' => '#dc2626', // darker red
                'EXPIRED' => '#6b7280', // gray
            ]);
    }

    /**
     * Determine the amount of time the results should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'order-execution-stats';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Order Status Distribution';
    }
}

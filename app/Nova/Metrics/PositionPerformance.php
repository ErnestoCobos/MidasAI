<?php

namespace App\Nova\Metrics;

use App\Models\Position;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Illuminate\Support\Facades\DB;

class PositionPerformance extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, Position::where('status', 'CLOSED'), function ($position) {
            $pnlPercentage = $position->getPnLPercentage();
            
            if ($pnlPercentage >= 5) return 'High Profit (>5%)';
            if ($pnlPercentage > 0) return 'Profit (0-5%)';
            if ($pnlPercentage > -5) return 'Small Loss (0-5%)';
            return 'Large Loss (>5%)';
        })->colors([
            'High Profit (>5%)' => '#22c55e',
            'Profit (0-5%)' => '#84cc16',
            'Small Loss (0-5%)' => '#f97316',
            'Large Loss (>5%)' => '#ef4444',
        ])->label(function ($value) {
            return $value;
        });
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
        return 'position-performance';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Position Performance Distribution';
    }
}

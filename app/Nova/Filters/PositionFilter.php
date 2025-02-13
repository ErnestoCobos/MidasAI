<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\Position;

class PositionFilter extends Filter
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
    public $name = 'Position Filter';

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
            'side' => $query->where('side', $value),
            'status' => $query->where('status', $value),
            'strategy' => $query->where('strategy_name', $value),
            'performance' => $this->applyPerformanceFilter($query, $value),
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
            'Position Side' => [
                'Long' => 'side:' . Position::SIDE_LONG,
                'Short' => 'side:' . Position::SIDE_SHORT,
            ],
            'Position Status' => [
                'Open' => 'status:' . Position::STATUS_OPEN,
                'Closed' => 'status:' . Position::STATUS_CLOSED,
            ],
            'Strategy' => $this->getStrategyOptions(),
            'Performance' => [
                'Profitable' => 'performance:profitable',
                'Loss Making' => 'performance:loss',
                'High Profit (>5%)' => 'performance:high_profit',
                'Large Loss (>5%)' => 'performance:large_loss',
            ],
        ];
    }

    /**
     * Get unique strategy names from positions.
     *
     * @return array
     */
    protected function getStrategyOptions()
    {
        $strategies = Position::distinct()
            ->pluck('strategy_name')
            ->filter()
            ->mapWithKeys(function ($strategy) {
                return [$strategy => 'strategy:' . $strategy];
            })
            ->toArray();

        return $strategies;
    }

    /**
     * Apply performance-based filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyPerformanceFilter($query, $value)
    {
        return match($value) {
            'profitable' => $query->whereRaw('(realized_pnl + unrealized_pnl) > 0'),
            'loss' => $query->whereRaw('(realized_pnl + unrealized_pnl) < 0'),
            'high_profit' => $query->whereRaw('((realized_pnl + unrealized_pnl) / (quantity * entry_price) * 100) >= 5'),
            'large_loss' => $query->whereRaw('((realized_pnl + unrealized_pnl) / (quantity * entry_price) * 100) <= -5'),
            default => $query,
        };
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

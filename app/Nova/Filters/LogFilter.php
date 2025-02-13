<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

class LogFilter extends Filter
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
    public $name = 'Log Filter';

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
            'level' => $query->where('level', $value),
            'component' => $query->where('component', $value),
            'time' => $this->applyTimeFilter($query, $value),
            'severity' => $this->applySeverityFilter($query, $value),
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
            'Log Level' => [
                'Debug' => 'level:' . SystemLog::LEVEL_DEBUG,
                'Info' => 'level:' . SystemLog::LEVEL_INFO,
                'Notice' => 'level:' . SystemLog::LEVEL_NOTICE,
                'Warning' => 'level:' . SystemLog::LEVEL_WARNING,
                'Error' => 'level:' . SystemLog::LEVEL_ERROR,
                'Critical' => 'level:' . SystemLog::LEVEL_CRITICAL,
                'Alert' => 'level:' . SystemLog::LEVEL_ALERT,
                'Emergency' => 'level:' . SystemLog::LEVEL_EMERGENCY,
            ],
            'Component' => $this->getComponentOptions(),
            'Time Range' => [
                'Last Hour' => 'time:hour',
                'Last 6 Hours' => 'time:6hours',
                'Last 12 Hours' => 'time:12hours',
                'Last 24 Hours' => 'time:24hours',
                'Last Week' => 'time:week',
            ],
            'Severity' => [
                'Errors Only' => 'severity:errors',
                'Warnings & Above' => 'severity:warnings',
                'Info & Above' => 'severity:info',
            ],
        ];
    }

    /**
     * Get unique components from logs.
     *
     * @return array
     */
    protected function getComponentOptions()
    {
        $components = SystemLog::distinct()
            ->pluck('component')
            ->filter()
            ->mapWithKeys(function ($component) {
                return [$component => 'component:' . $component];
            })
            ->toArray();

        return $components;
    }

    /**
     * Apply time-based filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTimeFilter($query, $value)
    {
        $now = now();

        return match($value) {
            'hour' => $query->where('logged_at', '>=', $now->subHour()),
            '6hours' => $query->where('logged_at', '>=', $now->subHours(6)),
            '12hours' => $query->where('logged_at', '>=', $now->subHours(12)),
            '24hours' => $query->where('logged_at', '>=', $now->subDay()),
            'week' => $query->where('logged_at', '>=', $now->subWeek()),
            default => $query,
        };
    }

    /**
     * Apply severity-based filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySeverityFilter($query, $value)
    {
        return match($value) {
            'errors' => $query->whereIn('level', [
                SystemLog::LEVEL_ERROR,
                SystemLog::LEVEL_CRITICAL,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_EMERGENCY,
            ]),
            'warnings' => $query->whereIn('level', [
                SystemLog::LEVEL_WARNING,
                SystemLog::LEVEL_ERROR,
                SystemLog::LEVEL_CRITICAL,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_EMERGENCY,
            ]),
            'info' => $query->whereIn('level', [
                SystemLog::LEVEL_INFO,
                SystemLog::LEVEL_NOTICE,
                SystemLog::LEVEL_WARNING,
                SystemLog::LEVEL_ERROR,
                SystemLog::LEVEL_CRITICAL,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_EMERGENCY,
            ]),
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

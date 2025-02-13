<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Http\Requests\NovaRequest;

class DeactivatePairs extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            // Check if there are open positions
            if ($fields->force_close || !$model->isTrading()) {
                $model->update(['is_active' => false]);
            }
        }

        return Action::message('Selected trading pairs have been deactivated successfully.');
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Boolean::make('Force Close', 'force_close')
                ->help('Deactivate pairs even if they have open positions')
                ->default(false),
        ];
    }

    /**
     * Get the displayable name of the action.
     *
     * @return string
     */
    public function name()
    {
        return 'Deactivate Trading Pairs';
    }

    /**
     * Mark the action as destructive.
     *
     * @return $this
     */
    public function destructive()
    {
        return $this;
    }

    /**
     * Determine if the action should be available for the given request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $resource
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public function authorizedToSee($request)
    {
        return $request->user()->can('manage_trading_pairs');
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public function authorizedToRun($request, $model)
    {
        return $this->authorizedToSee($request);
    }
}

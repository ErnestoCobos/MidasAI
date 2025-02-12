<?php

namespace App\Services\AI\DeepSeek;

use Illuminate\Support\ServiceProvider;

class DeepSeekServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('deepseek', function ($app) {
            return new DeepSeekManager($app);
        });
    }

    public function boot()
    {
        //
    }
}

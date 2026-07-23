<?php

namespace Elibrary\Lms;

use Illuminate\Support\ServiceProvider;

class LmsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lms');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

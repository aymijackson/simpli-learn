<?php

namespace Elibrary\Cbt;

use Illuminate\Support\ServiceProvider;

class CbtServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cbt');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

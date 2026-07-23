<?php

namespace Elibrary\Library;

use Illuminate\Support\ServiceProvider;

class LibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'library');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

<?php

namespace Elibrary\Library;

use Elibrary\Library\Documents\DocumentConverter;
use Elibrary\Library\Documents\LibreOfficeConverter;
use Illuminate\Support\ServiceProvider;

class LibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentConverter::class, fn () => new LibreOfficeConverter);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'library');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

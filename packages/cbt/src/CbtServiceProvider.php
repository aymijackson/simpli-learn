<?php

namespace Elibrary\Cbt;

use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Cbt\Support\StandaloneExamPlacements;
use Illuminate\Support\ServiceProvider;

class CbtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf(ExamPlacements::class, StandaloneExamPlacements::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cbt');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

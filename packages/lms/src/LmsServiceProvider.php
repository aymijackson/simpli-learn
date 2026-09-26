<?php

namespace Elibrary\Lms;

use Elibrary\Cbt\Contracts\ExamPlacements;
use Elibrary\Lms\Support\CourseExamPlacements;
use Illuminate\Support\ServiceProvider;

class LmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamPlacements::class, CourseExamPlacements::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lms');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

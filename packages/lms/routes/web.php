<?php

use Elibrary\Lms\Http\Controllers\CourseController;
use Elibrary\Lms\Http\Controllers\LessonController;
use Elibrary\Lms\Http\Controllers\Manage\CourseController as ManageCourseController;
use Elibrary\Lms\Http\Controllers\Manage\CourseModuleController as ManageCourseModuleController;
use Elibrary\Lms\Http\Controllers\Manage\LessonController as ManageLessonController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'module:lms'])
    ->prefix('t/{tenant}/lms')
    ->name('lms.')
    ->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course:slug}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
        Route::get('/courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::post('/courses/{course:slug}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('lessons.complete');

        Route::middleware('owner')->prefix('manage')->name('manage.')->group(function () {
            Route::get('/courses', [ManageCourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [ManageCourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [ManageCourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course:slug}/edit', [ManageCourseController::class, 'edit'])->name('courses.edit');
            Route::put('/courses/{course:slug}', [ManageCourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{course:slug}', [ManageCourseController::class, 'destroy'])->name('courses.destroy');

            Route::get('/courses/{course:slug}/lessons/create', [ManageLessonController::class, 'create'])->name('lessons.create');
            Route::post('/courses/{course:slug}/lessons', [ManageLessonController::class, 'store'])->name('lessons.store');
            Route::get('/courses/{course:slug}/lessons/{lesson}/edit', [ManageLessonController::class, 'edit'])->name('lessons.edit');
            Route::put('/courses/{course:slug}/lessons/{lesson}', [ManageLessonController::class, 'update'])->name('lessons.update');
            Route::delete('/courses/{course:slug}/lessons/{lesson}', [ManageLessonController::class, 'destroy'])->name('lessons.destroy');

            Route::post('/courses/{course:slug}/modules', [ManageCourseModuleController::class, 'store'])->name('modules.store');
            Route::put('/courses/{course:slug}/modules/{module}', [ManageCourseModuleController::class, 'update'])->name('modules.update');
            Route::delete('/courses/{course:slug}/modules/{module}', [ManageCourseModuleController::class, 'destroy'])->name('modules.destroy');
        });
    });

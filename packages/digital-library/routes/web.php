<?php

use Elibrary\Library\Http\Controllers\Manage\ResourceController as ManageResourceController;
use Elibrary\Library\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'module:library'])
    ->prefix('t/{tenant}/library')
    ->name('library.')
    ->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/resources/{resource:slug}', [ResourceController::class, 'show'])->name('resources.show');

        Route::middleware('owner')->prefix('manage')->name('manage.')->group(function () {
            Route::get('/resources', [ManageResourceController::class, 'index'])->name('resources.index');
            Route::get('/resources/create', [ManageResourceController::class, 'create'])->name('resources.create');
            Route::post('/resources', [ManageResourceController::class, 'store'])->name('resources.store');
            Route::get('/resources/{resource:slug}/edit', [ManageResourceController::class, 'edit'])->name('resources.edit');
            Route::put('/resources/{resource:slug}', [ManageResourceController::class, 'update'])->name('resources.update');
            Route::delete('/resources/{resource:slug}', [ManageResourceController::class, 'destroy'])->name('resources.destroy');
        });
    });

<?php

use Elibrary\Library\Http\Controllers\LibraryCheckoutController;
use Elibrary\Library\Http\Controllers\LibraryFavoriteController;
use Elibrary\Library\Http\Controllers\LibraryReaderController;
use Elibrary\Library\Http\Controllers\LibraryReadingProgressController;
use Elibrary\Library\Http\Controllers\LibraryResourceFileController;
use Elibrary\Library\Http\Controllers\LibraryResourcePurchaseController;
use Elibrary\Library\Http\Controllers\LibraryResourceRatingController;
use Elibrary\Library\Http\Controllers\Manage\LibraryAnalyticsController;
use Elibrary\Library\Http\Controllers\Manage\LibraryCheckoutController as ManageLibraryCheckoutController;
use Elibrary\Library\Http\Controllers\Manage\LibraryPaymentGatewayController as ManageLibraryPaymentGatewayController;
use Elibrary\Library\Http\Controllers\Manage\LibraryResourceFileController as ManageLibraryResourceFileController;
use Elibrary\Library\Http\Controllers\Manage\LibraryResourcePurchaseQueueController as ManageLibraryResourcePurchaseQueueController;
use Elibrary\Library\Http\Controllers\Manage\ResourceController as ManageResourceController;
use Elibrary\Library\Http\Controllers\ResourceController;
use Elibrary\Library\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Tenant-agnostic — mirrors the CBT/LMS webhook routes' shape.
Route::post('/webhooks/library/{gateway}', [WebhookController::class, 'handle'])->name('library.webhooks');

Route::middleware(['web', 'auth', 'module:library'])
    ->prefix('t/{tenant}/library')
    ->name('library.')
    ->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/resources/{resource:slug}', [ResourceController::class, 'show'])->name('resources.show');
        Route::get('/resources/{resource:slug}/files/{file}/download', [LibraryResourceFileController::class, 'download'])->name('resources.files.download');
        Route::get('/resources/{resource:slug}/files/{file}/stream', [LibraryResourceFileController::class, 'stream'])->name('resources.files.stream');
        Route::get('/resources/{resource:slug}/files/{file}/read', [LibraryReaderController::class, 'show'])->name('resources.files.read');
        Route::post('/resources/{resource:slug}/files/{file}/progress', [LibraryReadingProgressController::class, 'update'])->name('resources.files.progress');

        Route::get('/my-checkouts', [LibraryCheckoutController::class, 'myCheckouts'])->name('checkouts.index');
        Route::post('/resources/{resource:slug}/borrow', [LibraryCheckoutController::class, 'borrow'])->name('resources.borrow');
        Route::post('/checkouts/{checkout}/return', [LibraryCheckoutController::class, 'return'])->name('checkouts.return');
        Route::post('/holds/{hold}/claim', [LibraryCheckoutController::class, 'claimHold'])->name('holds.claim');

        Route::get('/resources/{resource:slug}/purchase', [LibraryResourcePurchaseController::class, 'create'])->name('resources.purchase.create');
        Route::post('/resources/{resource:slug}/purchase', [LibraryResourcePurchaseController::class, 'store'])->name('resources.purchase.store');
        Route::get('/resources/{resource:slug}/purchase/bank-transfer/{purchase}', [LibraryResourcePurchaseController::class, 'bankTransferShow'])->name('resources.purchase.bank-transfer.show');
        Route::post('/resources/{resource:slug}/purchase/bank-transfer/{purchase}', [LibraryResourcePurchaseController::class, 'bankTransferReport'])->name('resources.purchase.bank-transfer.report');

        Route::post('/resources/{resource:slug}/ratings', [LibraryResourceRatingController::class, 'store'])->name('resources.ratings.store');

        Route::get('/my-favorites', [LibraryFavoriteController::class, 'index'])->name('favorites.index');
        Route::post('/resources/{resource:slug}/favorite', [LibraryFavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('/resources/{resource:slug}/favorite', [LibraryFavoriteController::class, 'destroy'])->name('favorites.destroy');

        Route::middleware('owner')->prefix('manage')->name('manage.')->group(function () {
            Route::get('/resources', [ManageResourceController::class, 'index'])->name('resources.index');
            Route::get('/resources/create', [ManageResourceController::class, 'create'])->name('resources.create');
            Route::post('/resources', [ManageResourceController::class, 'store'])->name('resources.store');
            Route::get('/resources/{resource:slug}/edit', [ManageResourceController::class, 'edit'])->name('resources.edit');
            Route::put('/resources/{resource:slug}', [ManageResourceController::class, 'update'])->name('resources.update');
            Route::delete('/resources/{resource:slug}', [ManageResourceController::class, 'destroy'])->name('resources.destroy');

            Route::post('/resources/{resource:slug}/files', [ManageLibraryResourceFileController::class, 'store'])->name('resources.files.store');
            Route::delete('/resources/{resource:slug}/files/{file}', [ManageLibraryResourceFileController::class, 'destroy'])->name('resources.files.destroy');

            Route::get('/resources/{resource:slug}/checkouts', [ManageLibraryCheckoutController::class, 'index'])->name('resources.checkouts.index');
            Route::post('/resources/{resource:slug}/checkouts/{checkout}/force-return', [ManageLibraryCheckoutController::class, 'forceReturn'])->name('resources.checkouts.force-return');

            Route::get('/payment-gateways', [ManageLibraryPaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
            Route::put('/payment-gateways/{gateway}', [ManageLibraryPaymentGatewayController::class, 'update'])->name('payment-gateways.update');

            Route::get('/resource-purchases', [ManageLibraryResourcePurchaseQueueController::class, 'index'])->name('resource-purchases.index');
            Route::post('/resource-purchases/{purchase}/confirm', [ManageLibraryResourcePurchaseQueueController::class, 'confirm'])->name('resource-purchases.confirm');
            Route::post('/resource-purchases/{purchase}/reject', [ManageLibraryResourcePurchaseQueueController::class, 'reject'])->name('resource-purchases.reject');

            Route::get('/analytics', [LibraryAnalyticsController::class, 'index'])->name('analytics.index');
            Route::get('/analytics/export.csv', [LibraryAnalyticsController::class, 'exportCsv'])->name('analytics.export.csv');
        });
    });

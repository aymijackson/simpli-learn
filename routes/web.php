<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\MarketingPageController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\TenantApprovalController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CourseCertificateVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Public marketing site
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pages/{page:slug}', [MarketingController::class, 'show'])->name('pages.show');

// Public certificate verification (no auth, no tenant context)
Route::get('/certificates/verify/{token}', [CertificateVerificationController::class, 'show'])->name('certificates.verify');
Route::get('/course-certificates/verify/{token}', [CourseCertificateVerificationController::class, 'show'])->name('course-certificates.verify');

// Signup
Route::middleware('guest')->group(function () {
    Route::get('/signup', [RegistrationController::class, 'create'])->name('signup');
    Route::post('/signup', [RegistrationController::class, 'store']);
});
Route::get('/signup/pending', [RegistrationController::class, 'pending'])->name('signup.pending');

// Central auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});
Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Central admin
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{workspace}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::put('/tenants/{workspace}/modules', [AdminTenantController::class, 'updateModules'])->name('tenants.modules');
    Route::post('/tenants/{workspace}/approve', [TenantApprovalController::class, 'approve'])->name('tenants.approve');
    Route::post('/tenants/{workspace}/reject', [TenantApprovalController::class, 'reject'])->name('tenants.reject');
    Route::post('/tenants/{workspace}/suspend', [TenantApprovalController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{workspace}/reactivate', [TenantApprovalController::class, 'reactivate'])->name('tenants.reactivate');
    Route::post('/tenants/{workspace}/impersonate', [ImpersonationController::class, 'start'])->name('tenants.impersonate');

    Route::resource('pages', MarketingPageController::class)->except(['show']);

    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');

    Route::get('/payment-settings', [PaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
    Route::put('/payment-settings', [PaymentSettingsController::class, 'update'])->name('payment-settings.update');
    Route::put('/payment-settings/gateways/{gateway}', [PaymentSettingsController::class, 'updateGateway'])->name('payment-settings.gateways.update');
    Route::put('/tenants/{workspace}/revenue-split', [PaymentSettingsController::class, 'updateRevenueSplit'])->name('tenants.revenue-split');
});

// Tenant workspace
Route::prefix('t/{tenant}')->name('tenant.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);

        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', HomeController::class)->name('home');
        Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    });

    Route::middleware(['auth', 'owner'])->prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::put('/{member}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{member}', [TeamController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(['auth', 'owner'])->post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

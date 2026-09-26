<?php

use App\Http\Controllers\ActivityLogController;
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
use App\Http\Controllers\ManageDashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamImportController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Public marketing site
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pages/{page:slug}', [MarketingController::class, 'show'])->name('pages.show');
Route::get('/find-workspace', [MarketingController::class, 'findWorkspace'])->name('workspace.find');

// Public certificate verification (no auth, no tenant context)
Route::get('/certificates/verify/{token}', [CertificateVerificationController::class, 'show'])->name('certificates.verify');
Route::get('/course-certificates/verify/{token}', [CourseCertificateVerificationController::class, 'show'])->name('course-certificates.verify');

// Signup
Route::middleware('guest')->group(function () {
    Route::get('/signup', [RegistrationController::class, 'create'])->name('signup');
    Route::post('/signup', [RegistrationController::class, 'store'])->middleware('throttle:6,1');
});
Route::get('/signup/pending', [RegistrationController::class, 'pending'])->name('signup.pending');

// Central auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:6,1');
});
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
});

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::get('/export', [ProfileController::class, 'exportData'])->name('export');
    Route::post('/two-factor', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');
    Route::delete('/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
});

// Central admin
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity', [ActivityLogController::class, 'adminIndex'])->name('activity');

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
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:6,1');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:6,1');

        Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', HomeController::class)->name('home');
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::get('/profile/export', [ProfileController::class, 'exportData'])->name('profile.export');
        Route::post('/profile/two-factor', [TwoFactorController::class, 'enable'])->name('profile.two-factor.enable');
        Route::post('/profile/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('profile.two-factor.confirm');
        Route::post('/profile/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('profile.two-factor.cancel');
        Route::delete('/profile/two-factor', [TwoFactorController::class, 'disable'])->name('profile.two-factor.disable');
        Route::post('/profile/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('profile.two-factor.recovery-codes');
        Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    });

    Route::middleware(['auth', 'owner'])->get('/manage', ManageDashboardController::class)->name('manage.dashboard');
    Route::middleware(['auth', 'owner'])->get('/manage/activity', [ActivityLogController::class, 'tenantIndex'])->name('manage.activity');

    Route::middleware(['auth', 'owner'])->prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::get('/import', [TeamImportController::class, 'create'])->name('import.create');
        Route::get('/import/template.csv', [TeamImportController::class, 'template'])->name('import.template');
        Route::post('/import', [TeamImportController::class, 'store'])->name('import.store');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{member}', [TeamController::class, 'show'])->name('show');
        Route::put('/{member}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{member}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{member}/reactivate', [TeamController::class, 'reactivate'])->name('reactivate');
        Route::post('/{member}/invite', [TeamController::class, 'resendInvitation'])->name('invite');
        Route::get('/{member}/data', [TeamController::class, 'exportData'])->name('data.export');
        Route::post('/{member}/data/erase', [TeamController::class, 'eraseData'])->name('data.erase');
        Route::post('/{member}/two-factor/reset', [TeamController::class, 'resetTwoFactor'])->name('two-factor.reset');
    });

    Route::middleware(['auth', 'owner'])->post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

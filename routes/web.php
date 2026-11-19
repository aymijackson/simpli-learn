<?php

use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\SupportInboxController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\MarketingPageController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\SharedCourseController;
use App\Http\Controllers\Admin\TenantApprovalController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SingleSignOnController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\CourseCertificateVerificationController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManageDashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamImportController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

// Public marketing site
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pages/{page:slug}', [MarketingController::class, 'show'])->name('pages.show');
// Browsers send these in the background, sometimes with a cookie from before
// the user signed in. Starting a session here would answer with that stale
// guest cookie and sign the user straight back out, so this route touches
// neither the session nor cookies.
Route::post('/csp-report', CspReportController::class)->name('csp.report')->middleware('throttle:60,1')
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        \App\Http\Middleware\EnsureAccountIsActive::class,
        \App\Http\Middleware\SetLocale::class,
    ]);
Route::get('/find-workspace', [MarketingController::class, 'findWorkspace'])->name('workspace.find');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

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

// Google/Microsoft send everyone back here; the workspace is in the session.
Route::middleware('guest')->get('/auth/{provider}/callback', [SingleSignOnController::class, 'callback'])
    ->whereIn('provider', ['google', 'microsoft'])->middleware('throttle:20,1')->name('sso.callback');

// Uptime monitors: 200 when healthy, 503 with the failing check otherwise.
Route::get('/health', HealthController::class)->middleware('throttle:60,1')->name('health');

// Central admin
Route::middleware(['auth', 'two-factor'])->prefix('admin')->name('admin.')->group(function () {
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

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportInboxController::class, 'index'])->name('index');
        Route::get('/{conversation}', [SupportInboxController::class, 'show'])->whereNumber('conversation')->name('show');
        Route::get('/{conversation}/messages', [SupportInboxController::class, 'messages'])->whereNumber('conversation')->middleware('throttle:120,1')->name('messages');
        Route::post('/{conversation}/reply', [SupportInboxController::class, 'reply'])->whereNumber('conversation')->middleware('throttle:30,1')->name('reply');
        Route::post('/{conversation}/resolve', [SupportInboxController::class, 'resolve'])->whereNumber('conversation')->name('resolve');
    });

    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

    Route::get('/shared-courses', [SharedCourseController::class, 'index'])->name('shared-courses.index');
    Route::put('/shared-courses/{course}', [SharedCourseController::class, 'update'])->whereNumber('course')->name('shared-courses.update');

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

        Route::get('/auth/{provider}', [SingleSignOnController::class, 'redirect'])->whereIn('provider', ['google', 'microsoft'])->middleware('throttle:20,1')->name('sso.redirect');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', HomeController::class)->name('home');
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

        // Support chat bubble (learners → workspace owners, owners → platform).
        Route::get('/support/chat', [SupportChatController::class, 'index'])->middleware('throttle:120,1')->name('support.chat');
        Route::post('/support/chat', [SupportChatController::class, 'store'])->middleware('throttle:20,1')->name('support.chat.store');
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

    Route::middleware(['auth', 'owner'])->prefix('manage/support')->name('manage.support.')->group(function () {
        Route::get('/', [SupportInboxController::class, 'index'])->name('index');
        Route::get('/{conversation}', [SupportInboxController::class, 'show'])->whereNumber('conversation')->name('show');
        Route::get('/{conversation}/messages', [SupportInboxController::class, 'messages'])->whereNumber('conversation')->middleware('throttle:120,1')->name('messages');
        Route::post('/{conversation}/reply', [SupportInboxController::class, 'reply'])->whereNumber('conversation')->middleware('throttle:30,1')->name('reply');
        Route::post('/{conversation}/resolve', [SupportInboxController::class, 'resolve'])->whereNumber('conversation')->name('resolve');
    });

    Route::middleware(['auth', 'owner'])->group(function () {
        Route::get('/settings', [WorkspaceSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [WorkspaceSettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/api', [ApiTokenController::class, 'index'])->name('settings.api');
        Route::post('/settings/api', [ApiTokenController::class, 'store'])->name('settings.api.store');
        Route::delete('/settings/api/{token}', [ApiTokenController::class, 'destroy'])->whereNumber('token')->name('settings.api.destroy');
    });

    Route::middleware(['auth', 'owner'])->prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::get('/import', [TeamImportController::class, 'create'])->name('import.create');
        Route::get('/import/template.csv', [TeamImportController::class, 'template'])->name('import.template');
        Route::post('/import', [TeamImportController::class, 'store'])->name('import.store');
        Route::get('/job-titles', [JobTitleController::class, 'index'])->name('job-titles.index');
        Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])->name('job-titles.update');
        Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])->name('job-titles.destroy');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{member}', [TeamController::class, 'show'])->middleware('content:shared')->name('show');
        Route::put('/{member}', [TeamController::class, 'update'])->name('update');
        Route::put('/{member}/job-title', [TeamController::class, 'updateJobTitle'])->name('job-title');
        Route::delete('/{member}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{member}/reactivate', [TeamController::class, 'reactivate'])->name('reactivate');
        Route::post('/{member}/invite', [TeamController::class, 'resendInvitation'])->name('invite');
        Route::get('/{member}/data', [TeamController::class, 'exportData'])->name('data.export');
        Route::post('/{member}/data/erase', [TeamController::class, 'eraseData'])->name('data.erase');
        Route::post('/{member}/two-factor/reset', [TeamController::class, 'resetTwoFactor'])->name('two-factor.reset');
    });

    Route::middleware(['auth', 'owner'])->post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
});

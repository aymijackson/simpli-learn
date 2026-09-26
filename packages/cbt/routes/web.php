<?php

use Elibrary\Cbt\Http\Controllers\AttemptController;
use Elibrary\Cbt\Http\Controllers\AttemptQuestionController;
use Elibrary\Cbt\Http\Controllers\CertificateController;
use Elibrary\Cbt\Http\Controllers\CertificatePaymentController;
use Elibrary\Cbt\Http\Controllers\ExamController;
use Elibrary\Cbt\Http\Controllers\IntegrityEventController;
use Elibrary\Cbt\Http\Controllers\Manage\AnalyticsController as ManageAnalyticsController;
use Elibrary\Cbt\Http\Controllers\Manage\ExamPeopleReportController;
use Elibrary\Cbt\Http\Controllers\Manage\CertificatePaymentQueueController as ManageCertificatePaymentQueueController;
use Elibrary\Cbt\Http\Controllers\Manage\CertificateSettingsController as ManageCertificateSettingsController;
use Elibrary\Cbt\Http\Controllers\Manage\ExamController as ManageExamController;
use Elibrary\Cbt\Http\Controllers\Manage\ExamSectionController as ManageExamSectionController;
use Elibrary\Cbt\Http\Controllers\Manage\PaymentGatewayController as ManagePaymentGatewayController;
use Elibrary\Cbt\Http\Controllers\Manage\QuestionController as ManageQuestionController;
use Elibrary\Cbt\Http\Controllers\Manage\QuestionImportController as ManageQuestionImportController;
use Elibrary\Cbt\Http\Controllers\Manage\QuestionOptionController as ManageQuestionOptionController;
use Elibrary\Cbt\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Tenant-agnostic — a tenant's own gateway dashboard has one fixed callback
// URL, so the payment's own reference (not the URL) is what ties a webhook
// back to a tenant. No auth/CSRF; protected by per-gateway signature checks.
Route::post('/webhooks/certificates/{gateway}', [WebhookController::class, 'handle'])->name('cbt.webhooks.certificates');

Route::middleware(['web', 'auth', 'module:cbt'])
    ->prefix('t/{tenant}/cbt')
    ->name('cbt.')
    ->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam:slug}', [ExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam:slug}/start', [AttemptController::class, 'start'])->name('exams.start');
        Route::get('/attempts/{attempt}', [AttemptController::class, 'take'])->name('attempts.take');
        Route::post('/attempts/{attempt}', [AttemptController::class, 'submit'])->name('attempts.submit');
        Route::get('/attempts/{attempt}/result', [AttemptController::class, 'result'])->name('attempts.result');
        Route::get('/attempts/{attempt}/review', [AttemptQuestionController::class, 'review'])->name('attempts.review');
        Route::get('/attempts/{attempt}/questions/{page}', [AttemptQuestionController::class, 'show'])->whereNumber('page')->name('attempts.questions.show');
        Route::post('/attempts/{attempt}/questions/{page}', [AttemptQuestionController::class, 'answer'])->whereNumber('page')->name('attempts.questions.answer');
        Route::post('/attempts/{attempt}/integrity-events', [IntegrityEventController::class, 'store'])->middleware('throttle:30,1')->name('attempts.integrity.store');
        Route::get('/attempts/{attempt}/certificate', [CertificateController::class, 'download'])->name('attempts.certificate.download');

        Route::get('/attempts/{attempt}/certificate/pay', [CertificatePaymentController::class, 'create'])->name('attempts.certificate-payment.create');
        Route::post('/attempts/{attempt}/certificate/pay', [CertificatePaymentController::class, 'store'])->name('attempts.certificate-payment.store');
        Route::get('/attempts/{attempt}/certificate/pay/bank-transfer/{payment}', [CertificatePaymentController::class, 'bankTransferShow'])->name('attempts.certificate-payment.bank-transfer.show');
        Route::post('/attempts/{attempt}/certificate/pay/bank-transfer/{payment}', [CertificatePaymentController::class, 'bankTransferReport'])->name('attempts.certificate-payment.bank-transfer.report');

        Route::middleware('owner')->prefix('manage')->name('manage.')->group(function () {
            Route::get('/exams', [ManageExamController::class, 'index'])->name('exams.index');
            Route::get('/exams/create', [ManageExamController::class, 'create'])->name('exams.create');
            Route::post('/exams', [ManageExamController::class, 'store'])->name('exams.store');
            Route::get('/exams/{exam:slug}/edit', [ManageExamController::class, 'edit'])->name('exams.edit');
            Route::put('/exams/{exam:slug}', [ManageExamController::class, 'update'])->name('exams.update');
            Route::delete('/exams/{exam:slug}', [ManageExamController::class, 'destroy'])->name('exams.destroy');

            Route::post('/exams/{exam:slug}/sections', [ManageExamSectionController::class, 'store'])->name('sections.store');
            Route::put('/exams/{exam:slug}/sections/{section}', [ManageExamSectionController::class, 'update'])->name('sections.update');
            Route::delete('/exams/{exam:slug}/sections/{section}', [ManageExamSectionController::class, 'destroy'])->name('sections.destroy');

            Route::get('/exams/{exam:slug}/questions/create', [ManageQuestionController::class, 'create'])->name('questions.create');
            Route::post('/exams/{exam:slug}/questions', [ManageQuestionController::class, 'store'])->name('questions.store');

            Route::get('/exams/{exam:slug}/questions/import', [ManageQuestionImportController::class, 'create'])->name('questions.import.create');
            Route::get('/exams/{exam:slug}/questions/import/template', [ManageQuestionImportController::class, 'template'])->name('questions.import.template');
            Route::post('/exams/{exam:slug}/questions/import', [ManageQuestionImportController::class, 'store'])->name('questions.import.store');
            Route::get('/exams/{exam:slug}/questions/{question}/edit', [ManageQuestionController::class, 'edit'])->name('questions.edit');
            Route::put('/exams/{exam:slug}/questions/{question}', [ManageQuestionController::class, 'update'])->name('questions.update');
            Route::delete('/exams/{exam:slug}/questions/{question}', [ManageQuestionController::class, 'destroy'])->name('questions.destroy');

            Route::post('/exams/{exam:slug}/questions/{question}/options', [ManageQuestionOptionController::class, 'store'])->name('options.store');
            Route::put('/exams/{exam:slug}/questions/{question}/options/{option}', [ManageQuestionOptionController::class, 'update'])->name('options.update');
            Route::delete('/exams/{exam:slug}/questions/{question}/options/{option}', [ManageQuestionOptionController::class, 'destroy'])->name('options.destroy');

            Route::get('/analytics', [ManageAnalyticsController::class, 'index'])->name('analytics.index');
            Route::get('/analytics/{exam:slug}', [ManageAnalyticsController::class, 'show'])->name('analytics.show');
            Route::get('/analytics/{exam:slug}/export.csv', [ManageAnalyticsController::class, 'exportCsv'])->name('analytics.export.csv');
            Route::get('/analytics/{exam:slug}/people', [ExamPeopleReportController::class, 'show'])->name('analytics.people');
            Route::get('/analytics/{exam:slug}/people.csv', [ExamPeopleReportController::class, 'export'])->name('analytics.people.export');
            Route::get('/analytics/{exam:slug}/export.pdf', [ManageAnalyticsController::class, 'exportPdf'])->name('analytics.export.pdf');
            Route::get('/analytics/{exam:slug}/attempts/{attempt}/integrity', [ManageAnalyticsController::class, 'integrityEvents'])->name('analytics.attempts.integrity');

            Route::get('/certificates/settings', [ManageCertificateSettingsController::class, 'edit'])->name('certificates.settings.edit');
            Route::put('/certificates/settings', [ManageCertificateSettingsController::class, 'update'])->name('certificates.settings.update');

            Route::get('/payment-gateways', [ManagePaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
            Route::put('/payment-gateways/{gateway}', [ManagePaymentGatewayController::class, 'update'])->name('payment-gateways.update');

            Route::get('/certificate-payments', [ManageCertificatePaymentQueueController::class, 'index'])->name('certificate-payments.index');
            Route::post('/certificate-payments/{payment}/confirm', [ManageCertificatePaymentQueueController::class, 'confirm'])->name('certificate-payments.confirm');
            Route::post('/certificate-payments/{payment}/reject', [ManageCertificatePaymentQueueController::class, 'reject'])->name('certificate-payments.reject');
        });
    });

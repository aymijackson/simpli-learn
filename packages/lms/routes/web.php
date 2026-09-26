<?php

use Elibrary\Lms\Http\Controllers\CourseCertificateController;
use Elibrary\Lms\Http\Controllers\CoursePurchaseController;
use Elibrary\Lms\Http\Controllers\CourseController;
use Elibrary\Lms\Http\Controllers\CourseReviewController;
use Elibrary\Lms\Http\Controllers\LessonAttachmentController;
use Elibrary\Lms\Http\Controllers\LessonController;
use Elibrary\Lms\Http\Controllers\Manage\CourseController as ManageCourseController;
use Elibrary\Lms\Http\Controllers\Manage\CourseModuleController as ManageCourseModuleController;
use Elibrary\Lms\Http\Controllers\Manage\CoursePaymentGatewayController as ManageCoursePaymentGatewayController;
use Elibrary\Lms\Http\Controllers\Manage\CoursePurchaseQueueController as ManageCoursePurchaseQueueController;
use Elibrary\Lms\Http\Controllers\Manage\CourseReportController as ManageCourseReportController;
use Elibrary\Lms\Http\Controllers\Manage\LessonAttachmentController as ManageLessonAttachmentController;
use Elibrary\Lms\Http\Controllers\Manage\LessonController as ManageLessonController;
use Elibrary\Lms\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Tenant-agnostic — mirrors the CBT certificates webhook route's shape.
Route::post('/webhooks/courses/{gateway}', [WebhookController::class, 'handle'])->name('lms.webhooks.courses');

Route::middleware(['web', 'auth', 'module:lms'])
    ->prefix('t/{tenant}/lms')
    ->name('lms.')
    ->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course:slug}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
        Route::post('/courses/{course:slug}/reviews', [CourseReviewController::class, 'store'])->name('courses.reviews.store');
        Route::delete('/courses/{course:slug}/reviews/{review}', [CourseReviewController::class, 'destroy'])->name('courses.reviews.destroy');
        Route::get('/courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::post('/courses/{course:slug}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('lessons.complete');
        Route::get('/courses/{course:slug}/lessons/{lesson}/attachments/{attachment}/download', [LessonAttachmentController::class, 'download'])->name('lessons.attachments.download');
        Route::get('/courses/{course:slug}/lessons/{lesson}/attachments/{attachment}/stream', [LessonAttachmentController::class, 'stream'])->name('lessons.attachments.stream');

        Route::get('/courses/{course:slug}/purchase', [CoursePurchaseController::class, 'create'])->name('courses.purchase.create');
        Route::post('/courses/{course:slug}/purchase', [CoursePurchaseController::class, 'store'])->name('courses.purchase.store');
        Route::get('/courses/{course:slug}/purchase/bank-transfer/{purchase}', [CoursePurchaseController::class, 'bankTransferShow'])->name('courses.purchase.bank-transfer.show');
        Route::post('/courses/{course:slug}/purchase/bank-transfer/{purchase}', [CoursePurchaseController::class, 'bankTransferReport'])->name('courses.purchase.bank-transfer.report');

        Route::get('/courses/{course:slug}/certificate/purchase', [CoursePurchaseController::class, 'createCertificate'])->name('courses.certificate-purchase.create');
        Route::post('/courses/{course:slug}/certificate/purchase', [CoursePurchaseController::class, 'storeCertificate'])->name('courses.certificate-purchase.store');
        Route::get('/courses/{course:slug}/certificate', [CourseCertificateController::class, 'download'])->name('courses.certificate.download');

        Route::middleware('owner')->prefix('manage')->name('manage.')->group(function () {
            Route::get('/courses', [ManageCourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/create', [ManageCourseController::class, 'create'])->name('courses.create');
            Route::post('/courses', [ManageCourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course:slug}/edit', [ManageCourseController::class, 'edit'])->name('courses.edit');
            Route::get('/courses/{course:slug}/report', [ManageCourseReportController::class, 'show'])->name('courses.report');
            Route::get('/courses/{course:slug}/report.csv', [ManageCourseReportController::class, 'export'])->name('courses.report.export');
            Route::post('/courses/{course:slug}/assignments', [ManageCourseReportController::class, 'assign'])->name('courses.assign');
            Route::delete('/courses/{course:slug}/assignments/{assignment}', [ManageCourseReportController::class, 'unassign'])->name('courses.unassign');
            Route::put('/courses/{course:slug}', [ManageCourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{course:slug}', [ManageCourseController::class, 'destroy'])->name('courses.destroy');

            Route::get('/courses/{course:slug}/lessons/create', [ManageLessonController::class, 'create'])->name('lessons.create');
            Route::post('/courses/{course:slug}/lessons', [ManageLessonController::class, 'store'])->name('lessons.store');
            Route::get('/courses/{course:slug}/lessons/{lesson}/edit', [ManageLessonController::class, 'edit'])->name('lessons.edit');
            Route::put('/courses/{course:slug}/lessons/{lesson}', [ManageLessonController::class, 'update'])->name('lessons.update');
            Route::delete('/courses/{course:slug}/lessons/{lesson}', [ManageLessonController::class, 'destroy'])->name('lessons.destroy');

            Route::post('/courses/{course:slug}/lessons/{lesson}/attachments', [ManageLessonAttachmentController::class, 'store'])->name('lessons.attachments.store');
            Route::delete('/courses/{course:slug}/lessons/{lesson}/attachments/{attachment}', [ManageLessonAttachmentController::class, 'destroy'])->name('lessons.attachments.destroy');

            Route::post('/courses/{course:slug}/modules', [ManageCourseModuleController::class, 'store'])->name('modules.store');
            Route::put('/courses/{course:slug}/modules/{module}', [ManageCourseModuleController::class, 'update'])->name('modules.update');
            Route::delete('/courses/{course:slug}/modules/{module}', [ManageCourseModuleController::class, 'destroy'])->name('modules.destroy');

            Route::get('/payment-gateways', [ManageCoursePaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
            Route::put('/payment-gateways/{gateway}', [ManageCoursePaymentGatewayController::class, 'update'])->name('payment-gateways.update');

            Route::get('/course-purchases', [ManageCoursePurchaseQueueController::class, 'index'])->name('course-purchases.index');
            Route::post('/course-purchases/{purchase}/confirm', [ManageCoursePurchaseQueueController::class, 'confirm'])->name('course-purchases.confirm');
            Route::post('/course-purchases/{purchase}/reject', [ManageCoursePurchaseQueueController::class, 'reject'])->name('course-purchases.reject');
        });
    });

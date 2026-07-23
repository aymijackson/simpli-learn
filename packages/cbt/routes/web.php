<?php

use Elibrary\Cbt\Http\Controllers\AttemptController;
use Elibrary\Cbt\Http\Controllers\AttemptQuestionController;
use Elibrary\Cbt\Http\Controllers\ExamController;
use Elibrary\Cbt\Http\Controllers\Manage\ExamController as ManageExamController;
use Elibrary\Cbt\Http\Controllers\Manage\ExamSectionController as ManageExamSectionController;
use Elibrary\Cbt\Http\Controllers\Manage\QuestionController as ManageQuestionController;
use Elibrary\Cbt\Http\Controllers\Manage\QuestionOptionController as ManageQuestionOptionController;
use Illuminate\Support\Facades\Route;

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
            Route::get('/exams/{exam:slug}/questions/{question}/edit', [ManageQuestionController::class, 'edit'])->name('questions.edit');
            Route::put('/exams/{exam:slug}/questions/{question}', [ManageQuestionController::class, 'update'])->name('questions.update');
            Route::delete('/exams/{exam:slug}/questions/{question}', [ManageQuestionController::class, 'destroy'])->name('questions.destroy');

            Route::post('/exams/{exam:slug}/questions/{question}/options', [ManageQuestionOptionController::class, 'store'])->name('options.store');
            Route::put('/exams/{exam:slug}/questions/{question}/options/{option}', [ManageQuestionOptionController::class, 'update'])->name('options.update');
            Route::delete('/exams/{exam:slug}/questions/{question}/options/{option}', [ManageQuestionOptionController::class, 'destroy'])->name('options.destroy');
        });
    });

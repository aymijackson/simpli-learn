<?php

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Support\Facades\Route;

/*
| Workspace API, version 1 (served under /api/v1). Authenticate with
| "Authorization: Bearer elib_…" — a key created under Settings → API keys.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('api.token')->group(function () {
        Route::get('/people', [ApiController::class, 'people'])->name('people.index');
        Route::get('/courses', [ApiController::class, 'courses'])->name('courses.index');
        Route::get('/courses/{course}/progress', [ApiController::class, 'courseProgress'])->whereNumber('course')->name('courses.progress');
        Route::get('/exams', [ApiController::class, 'exams'])->name('exams.index');
        Route::get('/exams/{exam}/results', [ApiController::class, 'examResults'])->whereNumber('exam')->name('exams.results');
        Route::get('/certificates', [ApiController::class, 'certificates'])->name('certificates.index');
    });

    Route::middleware('api.token:write')->group(function () {
        Route::post('/people', [ApiController::class, 'addPerson'])->name('people.store');
        Route::post('/courses/{course}/assignments', [ApiController::class, 'assignCourse'])->whereNumber('course')->name('courses.assign');
    });
});

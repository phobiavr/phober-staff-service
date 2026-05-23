<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SnackController;
use App\Http\Controllers\TvController;
use Illuminate\Support\Facades\Route;

Route::middleware('otp.generate')->get('/otp/make', [OtpController::class, 'make']);
Route::middleware('otp')->get('/otp/submit', [OtpController::class, 'submit']);

Route::middleware('auth.server')->group(function () {
    Route::get('', [MeController::class, 'show']);

    Route::get('/employees', [EmployeeController::class, 'index']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'pay']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'cancel']);

    Route::get('/sessions/today', [SessionController::class, 'today']);
    Route::get('/sessions', [SessionController::class, 'active']);
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::delete('/sessions/{id}', [SessionController::class, 'cancel']);
    Route::put('/sessions/{id}/start', [SessionController::class, 'start']);
    Route::put('/sessions/{id}/finish', [SessionController::class, 'finish']);
    Route::get('/sessions/{id}/discount/{discount}', [SessionController::class, 'setDiscount']);

    Route::get('/snacks', [SnackController::class, 'index']);
    Route::post('/snacks', [SnackController::class, 'deal']);

    Route::post('/tv/token', [TvController::class, 'token']);
});

// TV PIN resolver (no auth required)
Route::get('/tv/pin/{pin}', [TvController::class, 'resolvePin']);

// TV sessions — validated by Laravel signed URL (APP_KEY), no staff login required
Route::middleware('signed')->get('/tv/sessions', [TvController::class, 'sessions'])->name('tv.sessions');

Route::middleware('private')->group(function () {
    Route::get('/sessions/{session}', [SessionController::class, 'show']);
});

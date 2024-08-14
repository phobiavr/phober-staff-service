<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.server')->get('', function () {
    return Auth::guard('server')->user();
});

Route::get('/otp-generate', function () {
    $otp = \Shared\OtpClient::generateOtp();

    return response()->json($otp);
});

Route::middleware('otp')->group(function () {
    Route::get('/otp-pass', function () {
        return true;
    });
});


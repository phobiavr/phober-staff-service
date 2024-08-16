<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Shared\Clients\NotificationClient;
use Shared\Clients\OtpClient;
use Shared\Notification\Channel;
use Shared\Notification\Provider;

Route::middleware('auth.server')->get('', function () {
    return Auth::guard('server')->user();
});

Route::middleware('otp.generate')->group(function () {
    Route::get('/discount-make', function () {
        return 'make';
    });
});

Route::middleware('otp')->group(function () {
    Route::get('/discount-submit', function () {
        return 'submit';
    });
});


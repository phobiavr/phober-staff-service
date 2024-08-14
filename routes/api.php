<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Shared\Clients\OtpClient;
use Shared\Notification\Channel;
use Shared\Notification\Provider;
use Shared\NotificationClient;

Route::middleware('auth.server')->get('', function () {
    return Auth::guard('server')->user();
});

Route::get('/otp-generate', function () {
    $otp = OtpClient::generateOtp();

    if ($otp->success) {
        $message = 'OTP: ' . $otp->code;

        NotificationClient::sendMessage(Provider::TELEGRAM, Channel::OTP, $message);
    }

    return response()->json($otp);
});

Route::middleware('otp')->group(function () {
    Route::get('/otp-pass', function () {
        return true;
    });
});


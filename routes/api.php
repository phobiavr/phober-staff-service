<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.server')->get('', function () {
    return Auth::guard('server')->user();
});

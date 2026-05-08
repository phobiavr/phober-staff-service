<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class OtpController extends BaseController {
    public function make(): string {
        return 'make';
    }

    public function submit(): string {
        return 'submit';
    }
}

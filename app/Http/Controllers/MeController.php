<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class MeController extends BaseController {
    public function show(): JsonResponse {
        return Response::json(Auth::guard('server')->user());
    }
}

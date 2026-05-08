<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;

class EmployeeController extends BaseController {
    public function __construct(private readonly EmployeeService $service) {
    }

    public function index(): JsonResponse {
        return Response::json(EmployeeResource::collection($this->service->all()));
    }
}

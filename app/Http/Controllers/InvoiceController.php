<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\IndexRequest;
use App\Http\Requests\Invoice\PayRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

class InvoiceController extends BaseController {
    public function __construct(private readonly InvoiceService $service) {
    }

    public function index(IndexRequest $request): JsonResponse {
        return Response::json(InvoiceResource::collection(
            $this->service->all($request->status(), $request->period())
        ));
    }

    public function pay(PayRequest $request, int $id): JsonResponse {
        $this->service->pay($id, $request->paymentMethod());

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    }

    public function cancel(int $id): JsonResponse {
        $this->service->cancel($id);

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    }
}

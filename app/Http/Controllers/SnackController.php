<?php

namespace App\Http\Controllers;

use App\Http\Requests\Snack\DealRequest;
use App\Http\Resources\SnackResource;
use App\Services\SnackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

class SnackController extends BaseController {
    public function __construct(private readonly SnackService $service) {
    }

    public function index(): JsonResponse {
        return Response::json(SnackResource::collection($this->service->all()));
    }

    public function deal(DealRequest $request): JsonResponse {
        $invoice = $this->service->deal(
            $request->snackId(),
            $request->quantity(),
            $request->invoiceId(),
            $request->customerId(),
            $request->customer(),
        );

        return Response::json(['invoice_id' => $invoice->id], ResponseFoundation::HTTP_CREATED);
    }
}

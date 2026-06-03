<?php

namespace App\Http\Controllers;

use App\Events\SessionCreated;
use App\Http\Requests\Session\StoreRequest;
use App\Http\Resources\SessionResource;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

class SessionController extends BaseController {
    public function __construct(private readonly SessionService $service) {
    }

    public function today(): JsonResponse {
        return Response::json(SessionResource::collection($this->service->today()));
    }

    public function active(): JsonResponse {
        return Response::json(SessionResource::collection($this->service->active()));
    }

    public function show(Session $session): JsonResponse {
        return Response::json(SessionResource::make($session));
    }

    public function store(StoreRequest $request): JsonResponse {
        $session = $this->service->create($request);

        return Response::json(SessionResource::make($session));
    }

    public function cancel(int $id): JsonResponse {
        $this->service->cancel($id);

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    }

    public function start(int $id): JsonResponse {
        $session = $this->service->start($id);

        return Response::json(SessionResource::make($session->load(['servicedBy', 'invoice'])));
    }

    public function finish(int $id): JsonResponse {
        $this->service->finish($id);

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    }

    public function setDiscount(int $id, float $discount): JsonResponse {
        $this->service->setDiscount($id, $discount);

        return Response::json(status: ResponseFoundation::HTTP_NO_CONTENT);
    }
}

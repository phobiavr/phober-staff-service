<?php

namespace App\Http\Controllers;

use App\Http\Resources\SessionResource;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class TvController extends BaseController {
    private const TTL_HOURS = 24;

    public function __construct(private readonly SessionService $service) {
    }

    public function token(): JsonResponse {
        $expiresAt = now()->addHours(self::TTL_HOURS);
        $url       = URL::temporarySignedRoute('tv.sessions', $expiresAt);

        return Response::json([
            'url'        => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function sessions(): JsonResponse {
        return Response::json(SessionResource::collection($this->service->active()));
    }
}

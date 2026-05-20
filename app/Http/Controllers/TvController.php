<?php

namespace App\Http\Controllers;

use App\Http\Resources\SessionResource;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class TvController extends BaseController {
    private const TTL_HOURS = 24;
    private const CACHE_PREFIX = 'tv_pin_';

    public function __construct(private readonly SessionService $service) {
    }

    public function token(): JsonResponse {
        $expiresAt = now()->addHours(self::TTL_HOURS);
        $url       = URL::temporarySignedRoute('tv.sessions', $expiresAt);
        $pin       = $this->generatePin();

        Cache::put(self::CACHE_PREFIX . $pin, $url, $expiresAt);

        return Response::json([
            'pin'        => $pin,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function resolvePin(string $pin): JsonResponse {
        $url = Cache::get(self::CACHE_PREFIX . $pin);

        if (!$url) {
            return Response::json(['message' => 'Invalid or expired PIN'], 404);
        }

        return Response::json(['url' => $url]);
    }

    public function sessions(): JsonResponse {
        return Response::json(SessionResource::collection($this->service->forTV()));
    }

    private function generatePin(): string {
        do {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Cache::has(self::CACHE_PREFIX . $pin));

        return $pin;
    }
}

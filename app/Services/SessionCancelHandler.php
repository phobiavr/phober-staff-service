<?php

namespace App\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Phobiavr\PhoberLaravelCommon\Contracts\SessionCancelHandlerInterface;

class SessionCancelHandler implements SessionCancelHandlerInterface {
    public function __construct(private readonly SessionService $sessions) {
    }

    public function handle(int $sessionId): void {
        try {
            $this->sessions->cancel($sessionId);
        } catch (ModelNotFoundException) {
            Log::info('Skipped rollback cancel: session already left QUEUE/ACTIVE.', [
                'session_id' => $sessionId,
            ]);
        }
    }
}

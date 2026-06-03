<?php

namespace App\Events;

use App\Events\Broadcast\SessionCreatedPrivate;
use App\Events\Broadcast\SessionCreatedPublic;
use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionCreated {
    use Dispatchable;
    use SerializesModels;

    public function __construct(Session $session, $action, $mins) {
        HandleSessionSchedule::dispatch($session->instance_id, $action, $mins, $session->id, $session->created_at->toIso8601String())
            ->onQueue('device');

        broadcast(new SessionCreatedPublic());
        broadcast(new SessionCreatedPrivate($session->id, $session->instance_id));
    }
}

<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionStarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(Session $session)
    {
        HandleSessionSchedule::dispatch(
            (int) $session->instance_id,
            'start',
            (int) $session->time,
            $session->id,
            $session->started_at->toIso8601String()
        )->onQueue('device');
    }
}

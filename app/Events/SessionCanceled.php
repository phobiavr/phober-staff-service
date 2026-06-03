<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionCanceled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(Session $session)
    {
        HandleSessionSchedule::dispatch((int) $session->instance_id, 'cancel')->onQueue('device');
    }
}

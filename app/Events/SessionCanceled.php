<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCanceled
{
    use Dispatchable;
    use SerializesModels;

    public int $instanceId;

    public function __construct(Session $session)
    {
        $this->instanceId = (int) $session->instance_id;
    }
}

<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreated
{
    use Dispatchable;
    use SerializesModels;

    public int $sessionId;
    public int $instanceId;

    public function __construct(Session $session)
    {
        $this->sessionId  = (int) $session->id;
        $this->instanceId = (int) $session->instance_id;
    }
}

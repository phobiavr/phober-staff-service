<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $sessionId;
    public int $instanceId;

    public function __construct(Session $session)
    {
        $this->sessionId  = (int) $session->id;
        $this->instanceId = (int) $session->instance_id;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('instances');
    }

    public function broadcastAs(): string
    {
        return 'SessionCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id'  => $this->sessionId,
            'instance_id' => $this->instanceId,
        ];
    }
}

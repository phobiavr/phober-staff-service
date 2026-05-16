<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreatedPrivate implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $instanceId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('instances')];
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

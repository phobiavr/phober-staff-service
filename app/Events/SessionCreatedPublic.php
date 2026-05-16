<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreatedPublic implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function broadcastOn(): Channel {
        return new Channel('sessions');
    }

    public function broadcastAs(): string
    {
        return 'SessionCreated';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}

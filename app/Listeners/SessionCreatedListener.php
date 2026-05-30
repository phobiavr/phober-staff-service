<?php

namespace App\Listeners;

use App\Events\Broadcast\SessionCreatedPrivate;
use App\Events\Broadcast\SessionCreatedPublic;
use App\Events\SessionCreated;

class SessionCreatedListener
{
    public function handle(SessionCreated $event): void
    {
        broadcast(new SessionCreatedPublic());
        broadcast(new SessionCreatedPrivate($event->sessionId, $event->instanceId));
    }
}

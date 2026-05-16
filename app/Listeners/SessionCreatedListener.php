<?php

namespace App\Listeners;

use App\Events\SessionCreated;
use App\Events\SessionCreatedPrivate;
use App\Events\SessionCreatedPublic;

class SessionCreatedListener
{
    public function handle(SessionCreated $event): void
    {
        broadcast(new SessionCreatedPublic());
        broadcast(new SessionCreatedPrivate($event->sessionId, $event->instanceId));
    }
}

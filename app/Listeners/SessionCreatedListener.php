<?php

namespace App\Listeners;

use App\Events\Broadcast\SessionCreatedPrivate;
use App\Events\Broadcast\SessionCreatedPublic;
use App\Events\SessionCreated;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionCreatedListener {
    public function handle(SessionCreated $event): void {
        $instanceId = (int) $event->session->instance_id;
        $createdAt = $event->session->created_at ?? now();

        HandleSessionSchedule::dispatch($instanceId, $event->action, $event->session->time, $event->session->id, $createdAt->toIso8601String())
            ->onQueue('device');

        broadcast(new SessionCreatedPublic());
        broadcast(new SessionCreatedPrivate($event->session->id, $instanceId));
    }
}

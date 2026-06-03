<?php

namespace App\Listeners;

use App\Events\Broadcast\SessionCreatedPrivate;
use App\Events\Broadcast\SessionCreatedPublic;
use App\Events\SessionCreated;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionCreatedListener {
    public function handle(SessionCreated $event): void {
        HandleSessionSchedule::dispatch($event->session->instance_id, $event->action, $event->session->time, $event->session->id, $event->session->created_at->toIso8601String())
            ->onQueue('device');

        broadcast(new SessionCreatedPublic());
        broadcast(new SessionCreatedPrivate($event->session->id, $event->session->instance_id));
    }
}

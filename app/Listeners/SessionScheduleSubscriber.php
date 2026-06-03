<?php

namespace App\Listeners;

use App\Events\SessionCanceled;
use App\Events\SessionCreated;
use App\Events\SessionFinished;
use App\Events\SessionStarted;
use Illuminate\Events\Dispatcher;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionScheduleSubscriber
{
    public function handleCanceled(SessionCanceled $event): void
    {
        HandleSessionSchedule::dispatch($event->session->instance_id, 'cancel')->onQueue('device');
    }

    public function handleStarted(SessionStarted $event): void
    {
        HandleSessionSchedule::dispatch($event->session->instance_id, 'start', $event->session->time)->onQueue('device');
    }

    public function handleFinished(SessionFinished $event): void
    {
        HandleSessionSchedule::dispatch($event->session->instance_id, 'finish')->onQueue('device');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SessionCreated::class, SessionCreatedListener::class);

        $events->listen(SessionStarted::class, [self::class, 'handleStarted']);
        $events->listen(SessionFinished::class, [self::class, 'handleFinished']);
        $events->listen(SessionFinished::class, [self::class, 'handleFinished']);
    }
}

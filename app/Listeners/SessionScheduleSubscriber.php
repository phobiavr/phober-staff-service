<?php

namespace App\Listeners;

use App\Events\SessionCanceled;
use App\Events\SessionFinished;
use App\Events\SessionStarted;
use Illuminate\Events\Dispatcher;
use Phobiavr\PhoberLaravelCommon\Jobs\HandleSessionSchedule;

class SessionScheduleSubscriber
{
    public function handleCanceled(SessionCanceled $event): void
    {
        HandleSessionSchedule::dispatch($event->instanceId, 'cancel')->onQueue('device');
    }

    public function handleStarted(SessionStarted $event): void
    {
        HandleSessionSchedule::dispatch($event->instanceId, 'start', $event->time)->onQueue('device');
    }

    public function handleFinished(SessionFinished $event): void
    {
        HandleSessionSchedule::dispatch($event->instanceId, 'finish')->onQueue('device');
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SessionCanceled::class, [self::class, 'handleCanceled']);
        $events->listen(SessionStarted::class, [self::class, 'handleStarted']);
        $events->listen(SessionFinished::class, [self::class, 'handleFinished']);
    }
}

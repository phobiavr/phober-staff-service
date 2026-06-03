<?php

namespace App\Providers;

use App\Events\SessionCreated;
use App\Listeners\SessionCreatedListener;
use App\Listeners\SessionScheduleSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        SessionCreated::class => [
            SessionCreatedListener::class,
        ],
    ];

    protected $subscribe = [
        SessionScheduleSubscriber::class,
    ];
}

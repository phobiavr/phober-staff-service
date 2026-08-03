<?php

namespace App\Providers;

use App\Events\SessionCreated;
use App\Listeners\SessionCreatedListener;
use App\Listeners\SessionScheduleSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /** @var array<int, class-string> */
    protected $subscribe = [
        SessionScheduleSubscriber::class,
    ];
}

<?php

namespace App\Providers;

use App\Events\SessionCreated;
use App\Listeners\SessionCreatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SessionCreated::class => [
            SessionCreatedListener::class,
        ],
    ];
}

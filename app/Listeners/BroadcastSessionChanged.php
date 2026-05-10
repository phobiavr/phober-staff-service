<?php

namespace App\Listeners;

use App\Events\SessionChanged;
use App\Events\SessionCreated;

class BroadcastSessionChanged
{
    public function handle(SessionCreated $event): void
    {
        SessionChanged::dispatch();
    }
}

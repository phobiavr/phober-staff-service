<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionFinished {
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Session $session) {
    }
}

<?php

namespace App\Events;

use App\Models\Session;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Phobiavr\PhoberLaravelCommon\Enums\SessionScheduleActionEnum;

class SessionCreated {
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Session $session, public SessionScheduleActionEnum $action) {

    }
}

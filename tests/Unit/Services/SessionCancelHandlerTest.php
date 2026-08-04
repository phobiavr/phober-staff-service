<?php

namespace Tests\Unit\Services;

use App\Services\SessionCancelHandler;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SessionCancelHandlerTest extends TestCase
{
    public function test_cancels_the_session_via_the_session_service(): void
    {
        $sessions = $this->createMock(SessionService::class);
        $sessions->expects($this->once())->method('cancel')->with(42);

        (new SessionCancelHandler($sessions))->handle(42);
    }

    public function test_swallows_a_model_not_found_exception_when_the_session_already_left_queue_or_active(): void
    {
        $sessions = $this->createMock(SessionService::class);
        $sessions->method('cancel')->willThrowException(new ModelNotFoundException());

        Log::shouldReceive('info')->once();

        // Should not throw — a lost race against the session's own lifecycle
        // isn't a failure worth retrying/reporting.
        (new SessionCancelHandler($sessions))->handle(42);

        $this->addToAssertionCount(1);
    }
}

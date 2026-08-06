<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Session;
use App\Models\SnackSale;
use App\Services\InvoiceService;
use App\Services\SessionCancelHandler;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use RuntimeException;
use Tests\TestCase;

class SessionCancelHandlerTest extends TestCase
{
    public function test_cancels_the_session_via_the_session_service(): void
    {
        $session = new Session();
        $session->invoice_id = null;

        $sessions = $this->createMock(SessionService::class);
        $sessions->expects($this->once())->method('cancel')->with(42)->willReturn($session);
        $invoices = $this->createMock(InvoiceService::class);

        (new SessionCancelHandler($sessions, $invoices))->handle(42);

        $this->addToAssertionCount(1);
    }

    public function test_swallows_a_model_not_found_exception_when_the_session_already_left_queue_or_active(): void
    {
        $sessions = $this->createMock(SessionService::class);
        $sessions->method('cancel')->willThrowException(new ModelNotFoundException());
        $invoices = $this->createMock(InvoiceService::class);
        $invoices->expects($this->never())->method('cancel');

        Log::shouldReceive('info')->once();

        // Should not throw — a lost race against the session's own lifecycle
        // isn't a failure worth retrying/reporting.
        (new SessionCancelHandler($sessions, $invoices))->handle(42);

        $this->addToAssertionCount(1);
    }

    public function test_logs_and_rethrows_an_unexpected_exception_so_the_queue_still_retries_it(): void
    {
        $sessions = $this->createMock(SessionService::class);
        $sessions->method('cancel')->willThrowException(new RuntimeException('db unreachable'));
        $invoices = $this->createMock(InvoiceService::class);

        Log::shouldReceive('error')->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('db unreachable');

        (new SessionCancelHandler($sessions, $invoices))->handle(42);
    }

    public function test_cancels_the_now_empty_invoice_after_rolling_back_its_only_session(): void
    {
        $invoice = Invoice::factory()->create();
        $session = Session::factory()->for($invoice)->create(['status' => SessionStatusEnum::QUEUE->value]);

        app(SessionCancelHandler::class)->handle($session->id);

        $this->assertSame(SessionStatusEnum::CANCELED->value, $session->fresh()->status);
        $this->assertSame(InvoiceStatusEnum::CANCELED->value, $invoice->fresh()->status);
    }

    public function test_leaves_the_invoice_open_when_another_session_still_holds_it(): void
    {
        $invoice = Invoice::factory()->create();
        $session = Session::factory()->for($invoice)->create(['status' => SessionStatusEnum::QUEUE->value]);
        Session::factory()->for($invoice)->create(['status' => SessionStatusEnum::ACTIVE->value]);

        app(SessionCancelHandler::class)->handle($session->id);

        $this->assertSame(InvoiceStatusEnum::QUEUE->value, $invoice->fresh()->status);
    }

    public function test_leaves_the_invoice_open_when_it_already_has_snack_sales(): void
    {
        $invoice = Invoice::factory()->create();
        $session = Session::factory()->for($invoice)->create(['status' => SessionStatusEnum::QUEUE->value]);
        SnackSale::factory()->for($invoice)->create();

        app(SessionCancelHandler::class)->handle($session->id);

        $this->assertSame(InvoiceStatusEnum::QUEUE->value, $invoice->fresh()->status);
    }

    public function test_leaves_a_non_queued_invoice_untouched(): void
    {
        $invoice = Invoice::factory()->payed()->create();
        $session = Session::factory()->for($invoice)->create(['status' => SessionStatusEnum::QUEUE->value]);

        app(SessionCancelHandler::class)->handle($session->id);

        $this->assertSame(InvoiceStatusEnum::PAYED->value, $invoice->fresh()->status);
    }
}

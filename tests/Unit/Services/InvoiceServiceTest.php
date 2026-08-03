<?php

namespace Tests\Unit\Services;

use App\Enums\PeriodFilterEnum;
use App\Models\Invoice;
use App\Models\Session;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\SnackSale;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, SnackSale::class, Invoice::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_lists_invoices_filtered_by_status_and_period_excluding_canceled_sessions_from_the_eager_load(): void
    {
        $matching = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value, 'created_at' => now()]);
        Session::factory()->for($matching)->canceled()->create();
        Session::factory()->for($matching)->finished()->create();

        Invoice::factory()->create(['status' => InvoiceStatusEnum::PAYED->value, 'created_at' => now()]);
        Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value, 'created_at' => now()->subMonths(2)]);

        $result = app(InvoiceService::class)->all(InvoiceStatusEnum::QUEUE, PeriodFilterEnum::MONTH);

        $this->assertCount(1, $result);
        $this->assertSame($matching->id, $result->first()->id);
        $this->assertCount(1, $result->first()->sessions);
    }

    public function test_pays_a_queued_invoice_and_records_the_payment_method(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);

        $paid = app(InvoiceService::class)->pay($invoice->id, ['CASH' => 50]);

        $this->assertSame(InvoiceStatusEnum::PAYED->value, $paid->fresh()->status);
        $this->assertSame(['CASH' => 50], $paid->fresh()->payment_method);
    }

    public function test_refuses_to_pay_an_invoice_that_is_not_queued(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::PAYED->value]);

        $this->expectException(ModelNotFoundException::class);
        app(InvoiceService::class)->pay($invoice->id, ['CASH' => 10]);
    }

    public function test_cancels_a_queued_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);

        $canceled = app(InvoiceService::class)->cancel($invoice->id);

        $this->assertSame(InvoiceStatusEnum::CANCELED->value, $canceled->fresh()->status);
    }

    public function test_reuses_an_existing_queued_invoice_by_id_ignoring_the_customer_arguments(): void
    {
        $existing = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value, 'customer' => 'Original']);

        $result = app(InvoiceService::class)->findOrCreateQueued($existing->id, 999, 'Ignored');

        $this->assertSame($existing->id, $result->id);
        $this->assertSame('Original', $result->customer);
    }

    public function test_does_not_reuse_an_invoice_id_that_is_not_queued_and_creates_a_new_invoice_instead(): void
    {
        $paid = Invoice::factory()->create(['status' => InvoiceStatusEnum::PAYED->value]);

        $result = app(InvoiceService::class)->findOrCreateQueued($paid->id, null, 'Quest');

        $this->assertNotSame($paid->id, $result->id);
        $this->assertSame('Quest', $result->customer);
    }

    public function test_resolves_the_customer_full_name_from_crm_service_when_a_customer_id_is_given(): void
    {
        Http::fake(['http://crm-service/customers/*' => Http::response(['full_name' => 'Jane Doe'])]);

        $result = app(InvoiceService::class)->findOrCreateQueued(null, 55, 'Quest');

        $this->assertSame('Jane Doe', $result->customer);
        $this->assertSame(55, $result->customer_id);
    }

    #[Group('slow')]
    public function test_falls_back_to_the_given_customer_name_when_crm_service_is_unreachable(): void
    {
        Http::fake(['http://crm-service/*' => fn () => throw new ConnectionException('refused')]);

        $result = app(InvoiceService::class)->findOrCreateQueued(null, 55, 'Quest');

        $this->assertSame('Quest', $result->customer);
    }

    public function test_today_cutoff_is_midnight_outside_the_grace_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 10, 0));

        $cutoff = app(InvoiceService::class)->todayCutoff();

        $this->assertTrue($cutoff->equalTo(Carbon::create(2026, 1, 15, 0, 0)));
    }

    public function test_today_cutoff_rolls_back_to_yesterday_during_the_grace_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 1, 30));

        $cutoff = app(InvoiceService::class)->todayCutoff();

        $this->assertTrue($cutoff->equalTo(Carbon::create(2026, 1, 14, 0, 0)));
    }

    public function test_assert_open_today_allows_an_invoice_created_earlier_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 10, 0));
        $invoice = Invoice::factory()->create([
            'status'     => InvoiceStatusEnum::QUEUE->value,
            'created_at' => Carbon::create(2026, 1, 15, 9, 0),
        ]);

        app(InvoiceService::class)->assertOpenToday($invoice->id);
        $this->assertTrue(true);
    }

    public function test_assert_open_today_allows_a_late_night_invoice_within_the_grace_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 1, 30));
        $invoice = Invoice::factory()->create([
            'status'     => InvoiceStatusEnum::QUEUE->value,
            'created_at' => Carbon::create(2026, 1, 14, 22, 0),
        ]);

        app(InvoiceService::class)->assertOpenToday($invoice->id);
        $this->assertTrue(true);
    }

    public function test_assert_open_today_rejects_a_late_night_invoice_once_the_grace_window_has_passed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 4, 0));
        $invoice = Invoice::factory()->create([
            'status'     => InvoiceStatusEnum::QUEUE->value,
            'created_at' => Carbon::create(2026, 1, 14, 22, 0),
        ]);

        $this->expectException(ValidationException::class);
        app(InvoiceService::class)->assertOpenToday($invoice->id);
    }

    public function test_assert_open_today_ignores_invoices_that_are_not_queued(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15, 10, 0));
        $invoice = Invoice::factory()->create([
            'status'     => InvoiceStatusEnum::PAYED->value,
            'created_at' => Carbon::create(2026, 1, 1, 0, 0),
        ]);

        app(InvoiceService::class)->assertOpenToday($invoice->id);
        $this->assertTrue(true);
    }

    public function test_assert_open_today_ignores_a_nonexistent_invoice_id(): void
    {
        app(InvoiceService::class)->assertOpenToday(999999);
        $this->assertTrue(true);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Session;
use App\Models\Snack;
use App\Models\SnackSale;
use App\Services\SnackService;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class SnackServiceTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, SnackSale::class, Invoice::class, Snack::class);
    }

    public function test_decrements_stock_and_records_a_snack_sale_snapshotting_name_and_price(): void
    {
        $snack = Snack::factory()->create(['name' => 'Cola', 'price' => 3.5, 'stock' => 10]);

        $invoice = app(SnackService::class)->deal($snack->id, 2, null, null, 'Quest');

        $this->assertSame(8, $snack->fresh()->stock);
        $this->assertCount(1, $invoice->snackSales);
        $this->assertSame('Cola', $invoice->snackSales->first()->snack);
        $this->assertSame(3.5, (float) $invoice->snackSales->first()->price);
    }

    public function test_attaches_the_snack_sale_to_an_existing_queued_invoice_when_given(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);
        $snack = Snack::factory()->create(['stock' => 5]);

        $result = app(SnackService::class)->deal($snack->id, 1, $invoice->id, null, 'Quest');

        $this->assertSame($invoice->id, $result->id);
    }

    public function test_allows_stock_to_go_negative_when_called_directly_bypassing_the_enough_stock_validation_rule(): void
    {
        $snack = Snack::factory()->create(['stock' => 1]);

        app(SnackService::class)->deal($snack->id, 5, null, null, 'Quest');

        $this->assertSame(-4, $snack->fresh()->stock);
    }
}

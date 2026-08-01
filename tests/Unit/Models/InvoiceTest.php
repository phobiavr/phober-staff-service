<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Session;
use App\Models\SnackSale;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearExistingRows(Session::class, SnackSale::class, Invoice::class);
    }

    public function test_sums_snack_sales_and_session_end_prices_into_total(): void
    {
        $invoice = Invoice::factory()->create();

        SnackSale::factory()->for($invoice)->create(['price' => 5, 'quantity' => 2]);
        Session::factory()->for($invoice)->finished()->create(['price' => 100, 'discount' => 0]);

        $this->assertSame(105.0, $invoice->fresh()->total);
    }

    public function test_includes_canceled_sessions_in_the_total_when_the_relation_is_not_pre_filtered(): void
    {
        // Invoice::getTotalAttribute() sums the raw `sessions` relation with no
        // status constraint — unlike InvoiceService::all(), which eager-loads
        // `sessions` excluding CANCELED. A freshly loaded model has no such
        // constraint, so a canceled session still counts towards `total`.
        $invoice = Invoice::factory()->create();

        Session::factory()->for($invoice)->canceled()->create(['price' => 40, 'discount' => 0]);

        $this->assertSame(40.0, $invoice->fresh()->total);
    }
}

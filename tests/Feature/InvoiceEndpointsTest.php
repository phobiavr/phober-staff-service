<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Session;
use App\Models\SnackSale;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Testing\ClearsExistingRows;
use Tests\TestCase;

class InvoiceEndpointsTest extends TestCase
{
    use ClearsExistingRows;

    protected function setUp(): void
    {
        parent::setUp();

        // Session/SnackSale have non-cascading FKs to invoices, so both must
        // be cleared before Invoice or the delete below would violate them.
        $this->clearExistingRows(Session::class, SnackSale::class, Invoice::class);
    }

    public function test_lists_invoices_filtered_by_status(): void
    {
        $this->authorizeAuthServer();

        Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);
        Invoice::factory()->create(['status' => InvoiceStatusEnum::PAYED->value]);

        $this->withToken('token')->getJson('/invoices?status=QUEUE')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_pays_a_queued_invoice_with_a_valid_payment_method_summing_to_the_total(): void
    {
        $this->authorizeAuthServer();
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);
        SnackSale::factory()->for($invoice)->create(['price' => 50, 'quantity' => 1]);

        $this->withToken('token')->putJson("/invoices/{$invoice->id}", ['method' => ['CASH' => 50]])
            ->assertNoContent();

        $this->assertSame(InvoiceStatusEnum::PAYED->value, $invoice->fresh()->status);
    }

    public function test_rejects_a_payment_whose_amounts_do_not_sum_to_the_invoice_total(): void
    {
        $this->authorizeAuthServer();
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);

        $this->withToken('token')->putJson("/invoices/{$invoice->id}", ['method' => ['CASH' => 999]])
            ->assertStatus(422);
    }

    public function test_cancels_a_queued_invoice(): void
    {
        $this->authorizeAuthServer();
        $invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::QUEUE->value]);

        $this->withToken('token')->deleteJson("/invoices/{$invoice->id}")->assertNoContent();

        $this->assertSame(InvoiceStatusEnum::CANCELED->value, $invoice->fresh()->status);
    }
}

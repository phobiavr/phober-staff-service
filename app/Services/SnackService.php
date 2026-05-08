<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Snack;
use Illuminate\Database\Eloquent\Collection;

class SnackService {
    public function __construct(private readonly InvoiceService $invoices) {
    }

    public function all(): Collection {
        return Snack::all();
    }

    public function deal(int $snackId, int $quantity, ?int $invoiceId, ?int $customerId, string $fallbackCustomer): Invoice {
        $invoice = $this->invoices->findOrCreateQueued($invoiceId, $customerId, $fallbackCustomer);

        $snack = Snack::findOrFail($snackId);
        $snack->stock -= $quantity;
        $snack->save();

        $invoice->snackSales()->create([
            'snack'    => $snack->name,
            'quantity' => $quantity,
            'price'    => $snack->price,
        ]);

        return $invoice;
    }
}

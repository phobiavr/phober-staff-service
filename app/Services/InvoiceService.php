<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Phobiavr\PhoberLaravelCommon\Clients\CrmClient;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;

class InvoiceService {
    public function all(): Collection {
        return Invoice::all();
    }

    public function pay(int $id, array $paymentMethod): Invoice {
        $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($id);

        $invoice->status = InvoiceStatusEnum::PAYED;
        $invoice->payment_method = $paymentMethod;
        $invoice->save();

        return $invoice;
    }

    public function cancel(int $id): Invoice {
        $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($id);

        $invoice->status = InvoiceStatusEnum::CANCELED;
        $invoice->save();

        return $invoice;
    }

    /**
     * Find an open invoice by id, or create a new one for the given customer.
     */
    public function findOrCreateQueued(?int $invoiceId, ?int $customerId, string $fallbackCustomer): Invoice {
        if ($invoiceId) {
            $existing = Invoice::where('id', $invoiceId)
                ->where('status', InvoiceStatusEnum::QUEUE)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $customerName = $fallbackCustomer;

        if ($customerId) {
            $response = CrmClient::customer($customerId);

            if (!$response->failed()) {
                $customerName = $response->json('full_name');
            }
        }

        return Invoice::create([
            'customer_id' => $customerId,
            'customer' => $customerName,
            'status' => InvoiceStatusEnum::QUEUE,
        ]);
    }
}

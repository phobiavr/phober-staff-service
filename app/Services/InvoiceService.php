<?php

namespace App\Services;

use App\Enums\PeriodFilterEnum;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Phobiavr\PhoberLaravelCommon\Clients\CrmClient;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\Exceptions\ServiceUnavailableException;

class InvoiceService {
    public function all(?InvoiceStatusEnum $status = null, ?PeriodFilterEnum $period = null): Collection {
        $query = Invoice::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($period) {
            $query->where('created_at', '>=', $period->startOf());
        }

        return $query->with(['sessions' => fn($q) => $q->where('status', '!=', SessionStatusEnum::CANCELED->value)])->get();
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
            try {
                $response = CrmClient::customer($customerId);

                if (!$response->failed()) {
                    $customerName = $response->json('full_name');
                }
            } catch (ServiceUnavailableException $e) {
                Log::error('Failed to resolve customer name: crm-service unreachable', [
                    'customer_id' => $customerId,
                    'message'     => $e->getMessage(),
                ]);
            }
        }

        return Invoice::create([
            'customer_id' => $customerId,
            'customer' => $customerName,
            'status' => InvoiceStatusEnum::QUEUE,
        ]);
    }
}

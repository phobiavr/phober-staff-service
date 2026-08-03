<?php

namespace App\Services;

use App\Enums\PeriodFilterEnum;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Phobiavr\PhoberLaravelCommon\Clients\CrmClient;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Phobiavr\PhoberLaravelCommon\Exceptions\ServiceUnavailableException;

class InvoiceService {
    // Hours after midnight during which an invoice opened "yesterday"
    // still counts as today's (a client may stay past 00:00).
    private const TODAY_GRACE_HOURS = 3;

    /** @return Collection<int, Invoice> */
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

    /** @param array<string, mixed> $paymentMethod */
    public function pay(int $id, array $paymentMethod): Invoice {
        $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($id);

        $invoice->status = InvoiceStatusEnum::PAYED->value;
        $invoice->payment_method = $paymentMethod;
        $invoice->save();

        return $invoice;
    }

    public function cancel(int $id): Invoice {
        $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($id);

        $invoice->status = InvoiceStatusEnum::CANCELED->value;
        $invoice->save();

        return $invoice;
    }

    public function todayCutoff(): Carbon {
        $now = now();

        return ($now->hour < self::TODAY_GRACE_HOURS ? $now->copy()->subDay() : $now->copy())->startOfDay();
    }

    /**
     * Guards against attaching new charges to a queued invoice from a previous business day.
     * A non-existent id or a non-queued invoice is left untouched — findOrCreateQueued()
     * already falls back to creating a fresh invoice for those cases.
     */
    public function assertOpenToday(int $invoiceId): void {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('status', InvoiceStatusEnum::QUEUE)
            ->first();

        if ($invoice && $invoice->created_at->lt($this->todayCutoff())) {
            throw ValidationException::withMessages([
                'invoice_id' => 'This invoice is no longer open for today.',
            ]);
        }
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
            'status' => InvoiceStatusEnum::QUEUE->value,
        ]);
    }
}

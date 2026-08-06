<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Phobiavr\PhoberLaravelCommon\Contracts\SessionCancelHandlerInterface;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;
use Phobiavr\PhoberLaravelCommon\Enums\SessionStatusEnum;
use Throwable;

readonly class SessionCancelHandler implements SessionCancelHandlerInterface {
    public function __construct(
        private SessionService $sessions,
        private InvoiceService $invoices,
    ) {
    }

    public function handle(int $sessionId): void {
        try {
            $session = $this->sessions->cancel($sessionId);
        } catch (ModelNotFoundException) {
            Log::info('Skipped rollback cancel: session already left QUEUE/ACTIVE.', [
                'session_id' => $sessionId,
            ]);

            return;
        } catch (Throwable $e) {
            Log::error('Saga compensation failed: could not roll back session after a CancelSession request.', [
                'session_id' => $sessionId,
                'message'    => $e->getMessage(),
            ]);

            throw $e;
        }

        $this->cancelInvoiceIfNowEmpty($session->invoice_id);
    }

    private function cancelInvoiceIfNowEmpty(?int $invoiceId): void {
        if (!$invoiceId) {
            return;
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('status', InvoiceStatusEnum::QUEUE->value)
            ->first();

        if (!$invoice) {
            return;
        }

        $hasRemainingSessions = $invoice->sessions()->where('status', '!=', SessionStatusEnum::CANCELED->value)->exists();
        $hasSnackSales        = $invoice->snackSales()->exists();

        if ($hasRemainingSessions || $hasSnackSales) {
            return;
        }

        try {
            $this->invoices->cancel($invoiceId);
        } catch (ModelNotFoundException) {
            // Invoice was paid/cancelled concurrently — nothing left to roll back.
        }
    }
}

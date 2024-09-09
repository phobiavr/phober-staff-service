<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Shared\Enums\InvoicePaymentMethodEnum;
use Shared\Enums\InvoiceStatusEnum;

class InvoiceRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        ValidatorFacade::extend('sum', function ($attribute, $value, $parameters, Validator $validator) {
            $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($this->route('id'));

            $totalSum = 0;
            $invoiceTotal = $invoice->total;

            foreach ($value as $method => $amount) {
                if ((!InvoicePaymentMethodEnum::tryFrom($method)) ||
                    (!is_numeric($amount) || $amount <= 0)) {
                    return false;
                }

                $totalSum += $amount;
            }

            if ($totalSum != $invoiceTotal) {
                return false;
            }

            return true;
        }, 'Invalid amount.');

        return [
            'method' => ['required', 'array', 'sum',],
        ];
    }
}

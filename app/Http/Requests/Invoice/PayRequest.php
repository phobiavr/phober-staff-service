<?php

namespace App\Http\Requests\Invoice;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Phobiavr\PhoberLaravelCommon\Enums\InvoicePaymentMethodEnum;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;

class PayRequest extends FormRequest {
    public function rules(): array {
        ValidatorFacade::extend('sum', function ($attribute, $value, $parameters, Validator $validator) {
            $invoice = Invoice::where('status', InvoiceStatusEnum::QUEUE->value)->findOrFail($this->route('id'));

            $totalSum     = 0;
            $invoiceTotal = $invoice->total;

            foreach ($value as $method => $amount) {
                if (!InvoicePaymentMethodEnum::tryFrom($method) || !is_numeric($amount) || $amount <= 0) {
                    return false;
                }

                $totalSum += $amount;
            }

            return $totalSum == $invoiceTotal;
        }, 'Invalid amount.');

        return [
            'method' => ['required', 'array', 'sum'],
        ];
    }

    public function paymentMethod(): array {
        return $this->input('method');
    }
}

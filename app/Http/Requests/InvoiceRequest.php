<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Shared\Enums\InvoicePaymentMethodEnum;

class InvoiceRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'method' => ['required', Rule::enum(InvoicePaymentMethodEnum::class)],
        ];
    }
}

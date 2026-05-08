<?php

namespace App\Http\Requests\Snack;

use App\Models\Snack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class DealRequest extends FormRequest {
    public function rules(): array {
        ValidatorFacade::extend('enough_stock', function ($attribute, $value, $parameters, Validator $validator) {
            $data  = $validator->getData();
            $snack = Snack::find($data['snack_id']);

            return $snack && $snack->stock >= $value;
        }, 'The requested quantity is not available in stock.');

        return [
            'snack_id'    => ['required', 'exists:snacks,id'],
            'quantity'    => ['required', 'enough_stock', 'gte:1'],
            'invoice_id'  => ['nullable', 'integer'],
            'customer_id' => ['nullable'],
            'customer'    => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void {
        if (!$this->filled('customer')) {
            $this->merge(['customer' => 'Quest']);
        }
    }

    public function snackId(): int {
        return (int) $this->input('snack_id');
    }

    public function quantity(): int {
        return (int) $this->input('quantity');
    }

    public function invoiceId(): ?int {
        return $this->filled('invoice_id') ? (int) $this->input('invoice_id') : null;
    }

    public function customerId(): ?int {
        return $this->filled('customer_id') ? (int) $this->input('customer_id') : null;
    }

    public function customer(): string {
        return $this->input('customer', 'Quest');
    }
}

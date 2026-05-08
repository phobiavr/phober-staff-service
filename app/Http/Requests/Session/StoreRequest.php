<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Phobiavr\PhoberLaravelCommon\Enums\SessionTimeEnum;

class StoreRequest extends FormRequest {
    public function rules(): array {
        return [
            'instance_id' => ['required'],
            'serviced_by' => ['required', 'exists:employees,id'],
            'time'        => ['required', Rule::enum(SessionTimeEnum::class)],
            'schedule'    => ['nullable', 'boolean'],
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

    public function instanceId(): int {
        return (int) $this->input('instance_id');
    }

    public function servicedBy(): int {
        return (int) $this->input('serviced_by');
    }

    public function time(): SessionTimeEnum {
        return SessionTimeEnum::from($this->input('time'));
    }

    public function isScheduled(): bool {
        return $this->boolean('schedule');
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

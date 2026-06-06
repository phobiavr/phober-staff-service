<?php

namespace App\Http\Requests\Invoice;

use App\Enums\PeriodFilterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Phobiavr\PhoberLaravelCommon\Enums\InvoiceStatusEnum;

class IndexRequest extends FormRequest {
    public function rules(): array {
        return [
            'status' => ['nullable', Rule::enum(InvoiceStatusEnum::class)],
            'period' => ['nullable', Rule::enum(PeriodFilterEnum::class)],
        ];
    }

    public function status(): ?InvoiceStatusEnum {
        $value = $this->query('status');

        return $value ? InvoiceStatusEnum::from($value) : null;
    }

    public function period(): ?PeriodFilterEnum {
        $value = $this->query('period');

        return $value ? PeriodFilterEnum::from($value) : null;
    }
}

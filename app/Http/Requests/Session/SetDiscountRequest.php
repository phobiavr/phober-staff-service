<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class SetDiscountRequest extends FormRequest {
    public function rules(): array {
        return [
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function discount(): float {
        return (float) $this->input('discount');
    }
}

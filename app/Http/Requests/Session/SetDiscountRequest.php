<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Phobiavr\PhoberLaravelCommon\Contracts\AuthUserInterface;

class SetDiscountRequest extends FormRequest {
    public function authorize(): bool {
        /** @var AuthUserInterface|null $user */
        $user = $this->user();

        return $user?->hasPermission('manage_discount') ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array {
        return [
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function discount(): float {
        return (float) $this->input('discount');
    }
}

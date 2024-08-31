<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Shared\Enums\SessionTimeEnum;

class SessionStoreRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'instance_id' => ['required'],
            'serviced_by' => ['required', 'exists:employees,id'],
            'tariff'      => ['required', Rule::enum(SessionTimeEnum::class)],
            'queue'       => ['bool']
        ];
    }
}

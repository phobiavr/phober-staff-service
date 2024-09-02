<?php

namespace App\Http\Requests;

use App\Models\Snack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

class SnackDealRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        ValidatorFacade::extend('enough_stock', function ($attribute, $value, $parameters, Validator $validator) {
            $data = $validator->getData();
            $snack = Snack::find($data['snack_id']);

            return $snack->stock >= $value;
        }, 'The requested quantity is not available in stock.');

        return [
            'snack_id' => ['required', 'exists:snacks,id'],
            'quantity' => ['required', 'enough_stock', 'gte:1'],
        ];
    }
}

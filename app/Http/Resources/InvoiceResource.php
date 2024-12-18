<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"             => $this->id,
            "customer_id"    => $this->customer_id,
            "status"         => $this->status,
            'sessions'       => SessionResource::collection($this->sessions),
            'snack_sales'    => SnackSaleResource::collection($this->snackSales),
            'payment_method' => $this->payment_method,
            'total'          => $this->total,
        ];
    }
}

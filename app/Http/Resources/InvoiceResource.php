<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $customer
 * @property string $status
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Session> $sessions
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SnackSale> $snackSales
 * @property array<string, mixed>|null $payment_method
 * @property float $total
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class InvoiceResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>|\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"             => $this->id,
            "customer_id"    => $this->customer_id,
            "customer"       => $this->customer,
            "status"         => $this->status,
            'sessions'       => SessionResource::collection($this->sessions),
            'snack_sales'    => SnackSaleResource::collection($this->snackSales),
            'payment_method' => $this->payment_method,
            'total'          => $this->total,
            'created_at'     => $this->created_at,
        ];
    }
}

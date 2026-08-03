<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int|null $instance_id
 * @property int|null $serviced_by
 * @property int|null $time
 * @property float|null $price
 * @property float $discount
 * @property float $end_price
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \App\Models\Employee|null $servicedBy
 * @property \App\Models\Invoice|null $invoice
 */
class SessionResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>|\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"          => $this->id,
            "instance_id" => $this->instance_id,
            "serviced_by" => $this->serviced_by,
            "time"        => $this->time,
            "price"       => $this->price,
            "discount"    => $this->discount,
            "end_price"   => $this->end_price,
            "status"           => $this->status,
            "created_at"       => $this->created_at,
            "started_at"       => $this->started_at,
            "serviced_by_name" => $this->servicedBy?->full_name,
            "customer"         => $this->invoice?->customer,
        ];
    }
}

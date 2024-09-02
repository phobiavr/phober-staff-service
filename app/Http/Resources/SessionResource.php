<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
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
            "status"      => $this->status,
        ];
    }
}

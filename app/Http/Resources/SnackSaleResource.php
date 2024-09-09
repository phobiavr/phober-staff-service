<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SnackSaleResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"       => $this->id,
            "snack"    => $this->snack,
            "quantity" => $this->quantity,
            "price"    => $this->price,
            "total"    => $this->total,
        ];
    }
}

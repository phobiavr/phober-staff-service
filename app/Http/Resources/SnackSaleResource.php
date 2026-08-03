<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $snack
 * @property int $quantity
 * @property float $price
 * @property float $total
 */
class SnackSaleResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>|\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\JsonSerializable
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

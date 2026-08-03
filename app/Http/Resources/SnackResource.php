<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property int $stock
 * @property float $price
 */
class SnackResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>|\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"    => $this->id,
            "name"  => $this->name,
            "stock" => $this->stock,
            "price" => $this->price,
        ];
    }
}

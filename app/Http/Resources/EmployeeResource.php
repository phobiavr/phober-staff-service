<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        return [
            "id"         => $this->id,
            'full_name'  => $this->full_name,
            "first_name" => $this->first_name,
            "last_name"  => $this->last_name,
            'serviced'   => [
                'in_a_day'           => $this->serviced_in_a_day,
                'minutes_in_a_day'   => $this->serviced_minutes_in_a_day,
                'in_a_week'          => $this->serviced_in_a_week,
                'minutes_in_a_week'  => $this->serviced_minutes_in_a_week,
                'in_a_month'         => $this->serviced_in_a_month,
                'minutes_in_a_month' => $this->serviced_minutes_in_a_month,
                'total'              => $this->serviced_total,
                'minutes_total'      => $this->serviced_minutes_total,
            ],
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'day_of_week' => $this->day_of_week,
            'is_active'   => (bool)$this->is_active,
            'slots'       => TimeSlotResource::collection($this->whenLoaded('slots')),
        ];
    }
}

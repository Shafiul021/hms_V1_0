<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'start_time' => $this->start_time,
            'end_time'   => $this->end_time,
            'is_blocked' => (bool)$this->is_blocked,
        ];
    }
}

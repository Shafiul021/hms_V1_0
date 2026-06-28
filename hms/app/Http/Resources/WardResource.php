<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'type'         => $this->type,
            'capacity'     => $this->capacity,
            'daily_rate'   => $this->daily_rate,
            'created_at'   => $this->created_at?->toISOString(),
            'beds'         => BedResource::collection($this->whenLoaded('beds')),
        ];
    }
}

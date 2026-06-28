<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'ward_id'     => $this->ward_id,
            'bed_number'  => $this->bed_number,
            'status'      => $this->status->value ?? $this->status,
            'ward'        => new WardResource($this->whenLoaded('ward')),
        ];
    }
}

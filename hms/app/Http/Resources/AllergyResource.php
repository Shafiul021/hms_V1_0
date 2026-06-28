<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllergyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'allergen' => $this->allergen,
            'severity' => $this->severity,
            'notes'    => $this->notes,
        ];
    }
}

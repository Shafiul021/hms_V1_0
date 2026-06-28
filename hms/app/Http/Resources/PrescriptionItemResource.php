<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'prescription_id' => $this->prescription_id,
            'medicine_id'     => $this->medicine_id,
            'dosage'          => $this->dosage,
            'frequency'       => $this->frequency,
            'duration'        => $this->duration,
            'medicine'        => new MedicineResource($this->whenLoaded('medicine')),
        ];
    }
}

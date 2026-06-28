<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'patient_id'    => $this->patient_id,
            'bed_id'        => $this->bed_id,
            'doctor_id'     => $this->doctor_id,
            'reason'        => $this->reason,
            'notes'         => $this->notes,
            'admitted_at'   => $this->admitted_at?->toISOString(),
            'discharged_at' => $this->discharged_at?->toISOString(),
            'is_active'     => is_null($this->discharged_at),
            'patient'       => new PatientResource($this->whenLoaded('patient')),
            'doctor'        => new DoctorResource($this->whenLoaded('doctor')),
            'bed'           => new BedResource($this->whenLoaded('bed')),
            'nursing_notes' => NursingNoteResource::collection($this->whenLoaded('nursingNotes')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'appointment_id' => $this->appointment_id,
            'doctor_id'      => $this->doctor_id,
            'patient_id'     => $this->patient_id,
            'notes'          => $this->notes,
            'doctor'         => new DoctorResource($this->whenLoaded('doctor')),
            'patient'        => new PatientResource($this->whenLoaded('patient')),
            'appointment'    => new AppointmentResource($this->whenLoaded('appointment')),
            'items'          => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}

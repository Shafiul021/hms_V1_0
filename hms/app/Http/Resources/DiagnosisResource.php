<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisResource extends JsonResource
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
            'icd_code'       => $this->icd_code,
            'description'    => $this->description,
            'notes'          => $this->notes,
            'diagnosed_at'   => $this->diagnosed_at,
            'doctor'         => new DoctorResource($this->whenLoaded('doctor')),
            'patient'        => new PatientResource($this->whenLoaded('patient')),
            'appointment'    => new AppointmentResource($this->whenLoaded('appointment')),
        ];
    }
}

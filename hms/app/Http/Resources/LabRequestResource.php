<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabRequestResource extends JsonResource
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
            'test_id'        => $this->test_id,
            'status'         => $this->status->value ?? $this->status,
            'requested_at'   => $this->requested_at,
            'doctor'         => new DoctorResource($this->whenLoaded('doctor')),
            'patient'        => new PatientResource($this->whenLoaded('patient')),
            'appointment'    => new AppointmentResource($this->whenLoaded('appointment')),
            'test'           => new LabTestResource($this->whenLoaded('test')),
            'result'         => new LabResultResource($this->whenLoaded('result')),
        ];
    }
}

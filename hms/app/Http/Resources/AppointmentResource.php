<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id'  => $this->doctor_id,
            'slot_id'    => $this->slot_id,
            'date'       => $this->date?->format('Y-m-d'),
            'status'     => $this->status->value ?? $this->status,
            'booked_by'  => $this->booked_by,
            'notes'      => $this->notes,
            'patient'    => new PatientResource($this->whenLoaded('patient')),
            'doctor'     => new DoctorResource($this->whenLoaded('doctor')),
            'slot'       => new TimeSlotResource($this->whenLoaded('slot')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

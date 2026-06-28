<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'patient_code'       => $this->patient_code,
            'dob'                => $this->dob?->format('Y-m-d'),
            'blood_type'         => $this->blood_type,
            'gender'             => $this->gender,
            'user'               => new UserResource($this->whenLoaded('user')),
            'allergies'          => AllergyResource::collection($this->whenLoaded('allergies')),
            'emergency_contacts' => EmergencyContactResource::collection($this->whenLoaded('emergencyContacts')),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}

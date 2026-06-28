<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'patient_id'     => $this->patient_id,
            'appointment_id' => $this->appointment_id,
            'status'         => $this->status,
            'total_amount'   => $this->total_amount,
            'paid_amount'    => $this->paid_amount,
            'due_date'       => $this->due_date ? $this->due_date->toIso8601String() : null,
            'issued_at'      => $this->issued_at ? $this->issued_at->toIso8601String() : null,
            'created_at'     => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'     => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'patient'        => new PatientResource($this->whenLoaded('patient')),
            'appointment'    => new AppointmentResource($this->whenLoaded('appointment')),
            'items'          => BillItemResource::collection($this->whenLoaded('items')),
            'payments'       => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}

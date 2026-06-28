<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'bill_id'      => $this->bill_id,
            'amount'       => $this->amount,
            'method'       => $this->method,
            'reference_no' => $this->reference_no,
            'paid_at'      => $this->paid_at ? $this->paid_at->toIso8601String() : null,
            'recorded_by'  => $this->recorded_by,
            'recorded_by_user' => new UserResource($this->whenLoaded('recordedBy')),
            'created_at'   => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'   => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}

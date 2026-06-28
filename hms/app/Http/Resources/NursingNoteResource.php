<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NursingNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'admission_id' => $this->admission_id,
            'nurse_id'     => $this->nurse_id,
            'note'         => $this->note,
            'recorded_at'  => $this->recorded_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'nurse'        => new UserResource($this->whenLoaded('nurse')),
        ];
    }
}

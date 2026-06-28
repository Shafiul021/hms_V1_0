<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class LabResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'lab_request_id'  => $this->lab_request_id,
            'technician_id'   => $this->technician_id,
            'notes'           => $this->notes,
            'is_abnormal'     => $this->is_abnormal,
            'result_at'       => $this->result_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
            'download_url'    => $this->result_file
                ? URL::temporarySignedRoute(
                    'lab-results.download',
                    now()->addMinutes(30),
                    ['id' => $this->id]
                )
                : null,
            'lab_request'     => new LabRequestResource($this->whenLoaded('labRequest')),
            'technician'      => new UserResource($this->whenLoaded('technician')),
        ];
    }
}

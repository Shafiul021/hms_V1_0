<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'bill_id'     => $this->bill_id,
            'item_type'   => $this->item_type,
            'description' => $this->description,
            'quantity'    => $this->quantity,
            'unit_price'  => $this->unit_price,
            'total'       => $this->total,
            'created_at'  => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}

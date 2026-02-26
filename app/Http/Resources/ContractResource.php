<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'unit_name' => $this->unit_name,
            'customer_name' => $this->customer_name,
            'rent_amount' => (float) $this->rent_amount,
            'start_date' => $this->start_date ? (is_object($this->start_date) ? $this->start_date->format('Y-m-d') : $this->start_date) : null,
            'end_date' => $this->end_date ? (is_object($this->end_date) ? $this->end_date->format('Y-m-d') : $this->end_date) : null,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
        ];
    }
}

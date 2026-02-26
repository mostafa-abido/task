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
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_method' => is_object($this->payment_method) ? $this->payment_method->value : $this->payment_method,
            'reference_number' => $this->reference_number,
            'paid_at' => $this->paid_at ? (is_object($this->paid_at) ? $this->paid_at->toIso8601String() : $this->paid_at) : null,
        ];
    }
}

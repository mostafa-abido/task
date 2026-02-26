<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalPaid = $this->payments->sum('amount');
        $remaining = max(0, (float) $this->total - (float) $totalPaid);

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
            'due_date' => $this->due_date ? (is_object($this->due_date) ? $this->due_date->format('Y-m-d') : $this->due_date) : null,
            'paid_at' => $this->paid_at ? (is_object($this->paid_at) ? $this->paid_at->toIso8601String() : $this->paid_at) : null,
            'remaining_balance' => round($remaining, 2),
            'contract' => $this->whenLoaded('contract', fn () => new ContractResource($this->contract)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'contract_id' => $this->resource['contract_id'],
            'total_invoiced' => $this->resource['total_invoiced'],
            'total_paid' => $this->resource['total_paid'],
            'outstanding_balance' => $this->resource['outstanding_balance'],
            'invoices_count' => $this->resource['invoices_count'],
            'latest_invoice_date' => $this->resource['latest_invoice_date'],
        ];
    }
}

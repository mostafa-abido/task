<?php

namespace App\DTOs;

use App\Http\Requests\StoreInvoiceRequest;

class CreateInvoiceDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly int $contract_id,
        public readonly string $due_date,
        public readonly int $tenant_id,
    ) {
    }

    /**
     * Create a DTO from the validated form request.
     */
    public static function fromRequest(StoreInvoiceRequest $request): self
    {
        $contract = $request->route('contract');
        return new self(
            contract_id: $contract->id,
            due_date: $request->validated('due_date'),
            tenant_id: $request->user()->tenant_id,
        );
    }
}

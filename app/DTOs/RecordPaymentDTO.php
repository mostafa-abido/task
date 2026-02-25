<?php

namespace App\DTOs;

use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;

class RecordPaymentDTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public readonly int $invoice_id,
        public readonly float $amount,
        public readonly PaymentMethod $payment_method,
        public readonly ?string $reference_number,
    ) {
    }

    /**
     * Create a DTO from the validated form request.
     */
    public static function fromRequest(StorePaymentRequest $request): self
    {
        $invoice = $request->route('invoice');
        return new self(
            invoice_id: $invoice->id,
            amount: (float) $request->validated('amount'),
            payment_method: PaymentMethod::from($request->validated('payment_method')),
            reference_number: $request->validated('reference_number'),
        );
    }
}

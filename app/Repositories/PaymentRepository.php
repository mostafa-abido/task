<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * Create a new payment.
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Get the total amount paid for the invoice.
     */
    public function getTotalPaidForInvoice(int $invoiceId): float
    {
        return (float) Payment::where('invoice_id', $invoiceId)->sum('amount');
    }
}

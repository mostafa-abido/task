<?php

namespace App\Repositories;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * Create a new payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment;

    /**
     * Get the total amount paid for the invoice.
     */
    public function getTotalPaidForInvoice(int $invoiceId): float;
}

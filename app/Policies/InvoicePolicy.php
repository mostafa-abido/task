<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can create invoices for the contract.
     */
    public function create(User $user, Contract $contract): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }
        return (int) $contract->tenant_id === (int) $user->tenant_id;
    }

    /**
     * Determine whether the user can view (list) invoices for the contract.
     */
    public function viewInvoices(User $user, Contract $contract): bool
    {
        return $this->create($user, $contract);
    }

    /**
     * Determine whether the user can view the contract's financial summary.
     */
    public function viewSummary(User $user, Contract $contract): bool
    {
        return $this->create($user, $contract);
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }
        return (int) $invoice->tenant_id === (int) $user->tenant_id;
    }

    /**
     * Determine whether the user can record a payment on the invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return false;
        }
        return (int) $invoice->tenant_id === (int) $user->tenant_id;
    }
}

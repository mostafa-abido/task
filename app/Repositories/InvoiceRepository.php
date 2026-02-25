<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Find an invoice by ID.
     */
    public function findById(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    /**
     * Get an invoice by ID or throw.
     */
    public function getByIdOrFail(int $id): Invoice
    {
        return Invoice::with(['contract', 'payments'])->findOrFail($id);
    }

    /**
     * Get invoices for the contract with optional filters.
     */
    public function getByContractId(int $contractId, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = Invoice::where('contract_id', $contractId)->with(['payments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('due_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('due_date', '<=', $filters['to_date']);
        }
        $query->orderByDesc('id');

        if (!empty($filters['per_page'])) {
            return $query->paginate((int) $filters['per_page']);
        }
        return $query->get();
    }

    /**
     * Get the next invoice sequence number for the tenant and month.
     */
    public function getNextSequenceForTenantMonth(int $tenantId, string $yyyyMm): int
    {
        $tenantPadded = str_pad((string) $tenantId, 3, '0', STR_PAD_LEFT);
        $prefix = "INV-{$tenantPadded}-{$yyyyMm}-";
        $last = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        if (!$last) {
            return 1;
        }
        $seq = (int) substr($last, strlen($prefix));
        return $seq + 1;
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update the invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }
}

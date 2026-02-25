<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * Find an invoice by ID.
     */
    public function findById(int $id): ?Invoice;

    /**
     * Get an invoice by ID or throw.
     */
    public function getByIdOrFail(int $id): Invoice;

    /**
     * Get invoices for the contract with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getByContractId(int $contractId, array $filters = []): Collection|LengthAwarePaginator;

    /**
     * Get the next invoice sequence number for the tenant and month.
     */
    public function getNextSequenceForTenantMonth(int $tenantId, string $yyyyMm): int;

    /**
     * Create a new invoice.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice;

    /**
     * Update the invoice.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data): Invoice;
}

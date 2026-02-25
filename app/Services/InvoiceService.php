<?php

namespace App\Services;

use App\DTOs\CreateInvoiceDTO;
use App\DTOs\RecordPaymentDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\ContractRepositoryInterface;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\PaymentRepositoryInterface;
use App\Services\Tax\TaxService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private ContractRepositoryInterface $contractRepo,
        private InvoiceRepositoryInterface $invoiceRepo,
        private PaymentRepositoryInterface $paymentRepo,
        private TaxService $taxService,
    ) {
    }

    /**
     * Create an invoice for the contract from the DTO.
     */
    public function createInvoice(CreateInvoiceDTO $dto): Invoice
    {
        $contract = $this->contractRepo->getByIdOrFail($dto->contract_id);
        if ($contract->status->value !== 'active') {
            throw new InvalidArgumentException('Contract must be active to create an invoice.');
        }
        if ($contract->tenant_id != $dto->tenant_id) {
            throw new InvalidArgumentException('Contract does not belong to tenant.');
        }

        return DB::transaction(function () use ($dto, $contract) {
            $subtotal = (float) $contract->rent_amount;
            $taxAmount = $this->taxService->calculateTotalTax($subtotal);
            $total = round($subtotal + $taxAmount, 2);
            $yyyyMm = date('Ym', strtotime($dto->due_date));
            $tenantIdPadded = str_pad((string) $dto->tenant_id, 3, '0', STR_PAD_LEFT);
            $seq = $this->invoiceRepo->getNextSequenceForTenantMonth($dto->tenant_id, $yyyyMm);
            $seqPadded = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-{$tenantIdPadded}-{$yyyyMm}-{$seqPadded}";

            return $this->invoiceRepo->create([
                'contract_id' => $dto->contract_id,
                'tenant_id' => $dto->tenant_id,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => InvoiceStatus::Pending,
                'due_date' => $dto->due_date,
            ]);
        });
    }

    /**
     * Record a payment against the invoice from the DTO.
     */
    public function recordPayment(RecordPaymentDTO $dto): Payment
    {
        $invoice = $this->invoiceRepo->getByIdOrFail($dto->invoice_id);
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new InvalidArgumentException('Cannot record payment on a cancelled invoice.');
        }
        $totalPaid = $this->paymentRepo->getTotalPaidForInvoice($invoice->id);
        $remaining = (float) $invoice->total - $totalPaid;
        if ($dto->amount <= 0 || $dto->amount > $remaining) {
            throw new InvalidArgumentException('Payment amount cannot exceed remaining balance.');
        }

        return DB::transaction(function () use ($dto, $invoice, $totalPaid) {
            $payment = $this->paymentRepo->create([
                'invoice_id' => $dto->invoice_id,
                'amount' => $dto->amount,
                'payment_method' => $dto->payment_method,
                'reference_number' => $dto->reference_number,
                'paid_at' => now(),
            ]);
            $newTotalPaid = $totalPaid + $dto->amount;
            $invoiceTotal = (float) $invoice->total;
            if (abs($newTotalPaid - $invoiceTotal) < 0.01) {
                $this->invoiceRepo->update($invoice, [
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ]);
            } else {
                $this->invoiceRepo->update($invoice, [
                    'status' => InvoiceStatus::PartiallyPaid,
                ]);
            }
            return $payment;
        });
    }

    /**
     * Get invoices for the contract with optional filters.
     */
    public function getInvoicesByContract(int $contractId, array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->invoiceRepo->getByContractId($contractId, $filters);
    }

    /**
     * Get the financial summary for the contract.
     *
     * @return array{contract_id: int, total_invoiced: float, total_paid: float, outstanding_balance: float, invoices_count: int, latest_invoice_date: ?string}
     */
    public function getContractSummary(int $contractId): array
    {
        $contract = $this->contractRepo->getByIdOrFail($contractId);
        $invoices = $this->invoiceRepo->getByContractId($contractId);
        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        $latestDate = null;
        foreach ($invoices as $inv) {
            $totalInvoiced += (float) $inv->total;
            $paid = $this->paymentRepo->getTotalPaidForInvoice($inv->id);
            $totalPaid += $paid;
            if ($inv->due_date && (!$latestDate || $inv->due_date->gt($latestDate))) {
                $latestDate = $inv->due_date;
            }
        }
        return [
            'contract_id' => $contractId,
            'total_invoiced' => round($totalInvoiced, 2),
            'total_paid' => round($totalPaid, 2),
            'outstanding_balance' => round($totalInvoiced - $totalPaid, 2),
            'invoices_count' => $invoices->count(),
            'latest_invoice_date' => $latestDate?->format('Y-m-d'),
        ];
    }
}

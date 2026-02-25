<?php

namespace App\Http\Controllers;

use App\DTOs\CreateInvoiceDTO;
use App\DTOs\RecordPaymentDTO;
use App\Http\Requests\ListInvoicesRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\ContractSummaryResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private InvoiceService $invoiceService
    ) {
    }

    /**
     * Store a newly created invoice for the contract.
     */
    public function store(StoreInvoiceRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('create', [Invoice::class, $contract]);
        $dto = CreateInvoiceDTO::fromRequest($request);
        try {
            $invoice = $this->invoiceService->createInvoice($dto);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['contract_id' => [$e->getMessage()]]);
        }
        return InvoiceResource::make($invoice->load('contract', 'payments'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a listing of invoices for the contract.
     */
    public function index(ListInvoicesRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('viewInvoices', $contract);
        $filters = $request->validated();
        $result = $this->invoiceService->getInvoicesByContract($contract->id, $filters);
        return InvoiceResource::collection($result)->response();
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);
        $invoice->load('contract', 'payments');
        return InvoiceResource::make($invoice)->response();
    }

    /**
     * Store a newly created payment for the invoice.
     */
    public function storePayment(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('recordPayment', $invoice);
        $dto = RecordPaymentDTO::fromRequest($request);
        try {
            $payment = $this->invoiceService->recordPayment($dto);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => [$e->getMessage()]]);
        }
        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the financial summary for the contract.
     */
    public function summary(Contract $contract): JsonResponse
    {
        $this->authorize('viewSummary', $contract);
        $data = $this->invoiceService->getContractSummary($contract->id);
        return ContractSummaryResource::make($data)->response();
    }
}

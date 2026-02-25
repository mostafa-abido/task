# Invoice Management API — Requirements & Checklist

A structured breakdown of the Laravel coding assessment. Use this to verify you've implemented everything.

---

## 1. Stack & Constraints

| Item | Requirement |
|------|-------------|
| **Stack** | Laravel 10+, PHP 8.1+ |
| **Time** | 2–3 hours (scope accordingly) |
| **No AI** | Must be your own work; live code review will verify this |

---

## 2. Request Flow (Must Follow)

```
HTTP Request
  → Form Request (validate only, no business logic)
  → Controller (create DTO, authorize via Policy, delegate to Service)
  → Policy (authorization: can user do this on this resource?)
  → Service (all business logic, uses Repositories + TaxService)
  → Repository (only place that touches Eloquent/DB)
  → API Resource (format response)
```

- **Controller**: thin — DTO from request, authorize, call service, return Resource.
- **Form Request**: validation + (optional) authorization prep; no business logic.
- **DTO**: immutable data carrier; no Eloquent, no logic.
- **Policy**: “can this user do this action on this resource?”.
- **Service**: validation rules, calculations, transactions, orchestration.
- **Repository**: all DB access; Service never uses Eloquent directly.

---

## 3. Domain Models & Database

### 3.1 Contract

| Field | Type | Notes |
|-------|------|--------|
| id | PK | |
| tenant_id | int/FK | Multi-tenancy |
| unit_name | string | |
| customer_name | string | |
| rent_amount | decimal | |
| start_date | date | |
| end_date | date | |
| status | enum | **draft**, **active**, **expired**, **terminated** |

- Migration, model, factory.
- Relationships: `hasMany` Invoice.

### 3.2 Invoice

| Field | Type | Notes |
|-------|------|--------|
| id | PK | |
| contract_id | FK | BelongsTo Contract |
| invoice_number | string | **Auto-generated** (see business rules) |
| subtotal | decimal | |
| tax_amount | decimal | |
| total | decimal | |
| status | enum | **pending**, **paid**, **partially_paid**, **overdue**, **cancelled** |
| due_date | date | |
| paid_at | nullable datetime | |

- Migration, model, factory.
- Relationships: `belongsTo` Contract, `hasMany` Payment.
- **tenant_id**: add if “relevant tables” includes Invoice for multi-tenancy (task says “tenant_id on relevant tables”).

### 3.3 Payment

| Field | Type | Notes |
|-------|------|--------|
| id | PK | |
| invoice_id | FK | BelongsTo Invoice |
| amount | decimal | |
| payment_method | enum | **cash**, **bank_transfer**, **credit_card** |
| reference_number | string | |
| paid_at | datetime | |

- Migration, model, factory.
- Relationship: `belongsTo` Invoice.

### 3.4 General Model Requirements

- Use **PHP 8.1 Backed Enums** for all status/type fields (Contract status, Invoice status, Payment payment_method).
- Eloquent: **HasMany** / **BelongsTo** as specified.
- **tenant_id** on “relevant tables” (at least Contract; clarify if Invoice/Payment need it or are reached via Contract).

---

## 4. Data Transfer Objects (DTOs)

### 4.1 CreateInvoiceDTO

- **Purpose**: data needed to create an invoice.
- **Fields**: `contract_id`, `due_date`, `tenant_id` (and any other needed from request).
- **Requirements**:
  - **readonly** properties (PHP 8.1+).
  - **No** Eloquent, **no** validation, **no** business logic.
  - Static **`fromRequest(StoreInvoiceRequest $request)`** (or similar) that builds DTO from validated data.
  - Immutable (no setters).

Note: Task example uses `$request->validated('contract_id')` etc.; if contract comes from route, you may pass `Contract $contract` into `fromRequest` and read `$contract->id` and `$contract->tenant_id` there.

### 4.2 RecordPaymentDTO

- **Purpose**: data needed to record a payment.
- **Fields**: `invoice_id`, `amount`, `payment_method`, `reference_number` (and anything else required, e.g. `paid_at` if not server-generated).
- **Requirements**: same as above — readonly, `fromRequest`, no framework/logic.

---

## 5. Tax Calculation (Strategy Pattern)

### 5.1 Interface

- **TaxCalculatorInterface** with: `calculate(float $amount): float`.

### 5.2 Concrete Calculators

- **VAT**: 15% of amount.
- **Municipal Fee**: 2.5% of amount.
- Each is a separate class implementing the interface.

### 5.3 TaxService

- Accepts **multiple** tax calculators (e.g. array or collection).
- Applies all of them to an amount (e.g. sum of each calculator’s result).
- Used by InvoiceService for invoice creation.

### 5.4 Registration

- **Service Provider**: bind concrete tax calculators (and possibly TaxService) so that adding a new tax (e.g. Tourism 5%) requires only:
  - One new class implementing `TaxCalculatorInterface`.
  - One binding/registration in the provider.
- **No** changes to existing calculator classes or existing InvoiceService logic (Open/Closed).

---

## 6. Repository Layer

### 6.1 Interfaces + Implementations

| Interface | Implementation | Purpose |
|-----------|----------------|---------|
| ContractRepositoryInterface | ContractRepository (Eloquent) | All Contract DB access |
| InvoiceRepositoryInterface | InvoiceRepository (Eloquent) | All Invoice DB access |
| PaymentRepositoryInterface | PaymentRepository (Eloquent) | All Payment DB access |

### 6.2 Binding

- In a **Service Provider**: bind each interface to its implementation (e.g. `ContractRepositoryInterface` → `ContractRepository`).

### 6.3 Usage

- **Services** type-hint **interfaces only** (e.g. `InvoiceRepositoryInterface`), never concrete classes.
- Methods should be **focused**: e.g. `findById()`, `create()`, `getByContractId()`, `getNextInvoiceSequence()`, etc., as needed by InvoiceService.

### 6.4 Rule

- **Service layer must never call Eloquent directly**; all DB access goes through repositories.

---

## 7. Invoice Service (Business Logic)

### 7.1 Dependencies (constructor)

- `ContractRepositoryInterface`
- `InvoiceRepositoryInterface`
- `PaymentRepositoryInterface`
- `TaxService`

### 7.2 Methods

1. **createInvoice(CreateInvoiceDTO $dto): Invoice**
   - Validate contract is **active** (otherwise reject).
   - Calculate subtotal (e.g. from contract rent).
   - Use **TaxService** to compute tax_amount (VAT + Municipal, etc.).
   - Generate **invoice_number** (see format below).
   - Persist via InvoiceRepository (and any related persistence).
   - **Transaction**: wrap full creation in DB transaction.

2. **recordPayment(RecordPaymentDTO $dto): Payment**
   - Validate payment amount ≤ remaining balance of invoice.
   - Validate invoice is not **cancelled** (policy also checks this).
   - Persist payment via PaymentRepository.
   - Update invoice status:
     - If total payments **equal** invoice total → **paid**, set `paid_at`.
     - If partial → **partially_paid**.
   - **Transaction**: wrap update + payment create in DB transaction.

3. **getContractSummary(int $contractId): array**
   - Return: total_invoiced, total_paid, outstanding_balance (and any other required summary fields).
   - Used for “financial summary” endpoint.

### 7.3 Business Rules (Must Implement)

| Rule | Detail |
|------|--------|
| Invoice number | Format: `INV-{TENANT_ID}-{YYYYMM}-{SEQUENCE}` e.g. `INV-001-202602-0001`. Sequence per tenant per month. |
| Contract for invoice | Only **active** contracts can have new invoices. |
| Payment amount | Cannot exceed **remaining balance** of the invoice. |
| Invoice status | When payments total = invoice total → **paid**; when partial → **partially_paid**. |
| Transactions | All multi-step operations (create invoice, record payment) in **database transactions**. |

---

## 8. Authorization Policies

### 8.1 InvoicePolicy

- **create(User $user, Contract $contract)**  
  - Allow only if contract belongs to user’s tenant (e.g. `$contract->tenant_id === $user->tenant_id`).

- **view(User $user, Invoice $invoice)**  
  - Allow only if invoice belongs to user’s tenant (e.g. via contract or invoice’s tenant_id if present).

- **recordPayment(User $user, Invoice $invoice)**  
  - Allow only if invoice belongs to user’s tenant **and** invoice is not **cancelled**.

### 8.2 Controller

- Use **`$this->authorize('create', [Invoice::class, $contract])`** (or equivalent) **before** calling the service.
- Same for `view` and `recordPayment` on the appropriate resources.

---

## 9. Controller & API Endpoints

### 9.1 Endpoints

| Method | Route | Description |
|--------|--------|-------------|
| POST | `/api/contracts/{id}/invoices` | Create invoice for contract |
| GET | `/api/contracts/{id}/invoices` | List invoices for contract (with filters) |
| GET | `/api/invoices/{id}` | Get invoice details with payments |
| POST | `/api/invoices/{id}/payments` | Record a payment |
| GET | `/api/contracts/{id}/summary` | Financial summary for contract |

### 9.2 Controller Requirements

- **Thin**: create DTO from Form Request, authorize via Policy, call Service, return API Resource.
- **Form Requests** for validation (e.g. `StoreInvoiceRequest`, `StorePaymentRequest`).
- **API Resources** for JSON responses.
- **Route model binding** where it makes sense (e.g. `Contract $contract`, `Invoice $invoice`).
- **HTTP status codes**: 201 for create, 422 validation, 404 not found, 403 forbidden.

---

## 10. API Resources

### 10.1 InvoiceResource

- Fields: id, invoice_number, subtotal, tax_amount, total, status, due_date, paid_at.
- **Computed**: `remaining_balance`.
- **Conditional**: `contract` (whenLoaded), `payments` (whenLoaded).

### 10.2 PaymentResource

- Fields: id, amount, payment_method, reference_number, paid_at.

### 10.3 ContractSummaryResource

- Fields: contract_id, total_invoiced, total_paid, outstanding_balance, invoices_count, latest_invoice_date.

---

## 11. Form Requests

- **StoreInvoiceRequest**: validate `contract_id`, `due_date` (and any other input); optionally inject Contract from route.
- **StorePaymentRequest**: validate `amount`, `payment_method`, `reference_number` (and any other input).
- **ListInvoicesRequest** (if you use query params): validate filters (e.g. status, date range) for GET list.
- No business logic inside; only validation (and possibly preparing data for DTO).

---

## 12. Bonus (Optional)

- Observer or Event/Listener (e.g. log when invoice paid, notification on payment).
- Global Scope on `tenant_id` for multi-tenancy.
- Custom exceptions (e.g. `ContractNotActiveException`, `InsufficientBalanceException`) with HTTP mapping.
- Artisan command: mark overdue invoices (due_date < today, status still pending).
- Decorator: wrap a repository with caching.
- Pagination and filtering on GET `/api/contracts/{id}/invoices` (e.g. by status, date range).

---

## 13. Quick Checklist (Nothing Missed)

- [ ] Migrations: Contract, Invoice, Payment (with tenant_id where required).
- [ ] Models: relationships, enums for status/payment_method.
- [ ] Factories: Contract, Invoice, Payment.
- [ ] Enums: ContractStatus, InvoiceStatus, PaymentMethod (backed enums).
- [ ] CreateInvoiceDTO & RecordPaymentDTO (readonly, fromRequest, no logic).
- [ ] TaxCalculatorInterface + VAT + MunicipalFee + TaxService; registered in Service Provider.
- [ ] ContractRepositoryInterface + implementation + binding.
- [ ] InvoiceRepositoryInterface + implementation + binding.
- [ ] PaymentRepositoryInterface + implementation + binding.
- [ ] InvoiceService: createInvoice, recordPayment, getContractSummary; all rules and transactions.
- [ ] InvoicePolicy: create, view, recordPayment; tenant + cancelled check.
- [ ] Form Requests: store invoice, store payment (and list if filtered).
- [ ] InvoiceController: all 5 endpoints; authorize, DTO, service, Resource.
- [ ] InvoiceResource, PaymentResource, ContractSummaryResource.
- [ ] Routes: registered under `api` with correct HTTP methods and parameters.

---

## 14. Common Pitfalls to Avoid

1. **Business logic in Controller or Form Request** — keep it in Service.
2. **Eloquent in Service** — use only Repositories.
3. **Validation or logic inside DTOs** — DTOs are plain data carriers.
4. **Forgetting transactions** — create invoice and record payment must be transactional.
5. **Wrong invoice number format** — exact format INV-{TENANT_ID}-{YYYYMM}-{SEQUENCE}.
6. **Allowing payment on cancelled invoice** — policy + service must both enforce.
7. **Not checking contract active** — before creating invoice.
8. **Not checking payment amount vs remaining balance** — before recording payment.
9. **Policy not used** — controller must call `authorize()` before service calls.
10. **User model** — ensure User has `tenant_id` (or equivalent) for policy checks.

Use this document as your single source of truth while implementing. Good luck with your assessment.

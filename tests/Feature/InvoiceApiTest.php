<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'tenant_id' => 1,
        ]);
        $this->contract = Contract::factory()->active()->create([
            'tenant_id' => 1,
            'unit_name' => 'Unit 101',
            'customer_name' => 'Test Customer',
            'rent_amount' => 1000,
        ]);
    }

    public function test_create_invoice_returns_201_and_invoice_resource(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/contracts/{$this->contract->id}/invoices", [
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'subtotal',
                    'tax_amount',
                    'total',
                    'status',
                    'due_date',
                    'remaining_balance',
                ],
            ]);
        $this->assertStringStartsWith('INV-001-', $response->json('data.invoice_number'));
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $this->contract->id,
            'tenant_id' => 1,
        ]);
    }

    public function test_list_invoices_returns_200_and_collection(): void
    {
        Invoice::factory()->count(2)->create([
            'contract_id' => $this->contract->id,
            'tenant_id' => 1,
        ]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/contracts/{$this->contract->id}/invoices");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_show_invoice_returns_200_and_invoice_with_relations(): void
    {
        $invoice = Invoice::factory()->create([
            'contract_id' => $this->contract->id,
            'tenant_id' => 1,
        ]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'subtotal',
                    'total',
                    'remaining_balance',
                    'contract',
                    'payments',
                ],
            ]);
    }

    public function test_record_payment_returns_201_and_updates_invoice_status(): void
    {
        $invoice = Invoice::factory()->create([
            'contract_id' => $this->contract->id,
            'tenant_id' => 1,
            'total' => 100,
        ]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/invoices/{$invoice->id}/payments", [
            'amount' => 50,
            'payment_method' => 'cash',
            'reference_number' => 'REF-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'amount', 'payment_method', 'reference_number', 'paid_at']]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'amount' => 50]);
        $invoice->refresh();
        $status = $invoice->status;
        $this->assertEquals('partially_paid', is_object($status) ? $status->value : $status);
    }

    public function test_contract_summary_returns_200_with_correct_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/contracts/{$this->contract->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'contract_id',
                    'total_invoiced',
                    'total_paid',
                    'outstanding_balance',
                    'invoices_count',
                    'latest_invoice_date',
                ],
            ])
            ->assertJsonPath('data.contract_id', $this->contract->id);
    }

    public function test_create_invoice_fails_for_non_active_contract(): void
    {
        $draftContract = Contract::factory()->create([
            'tenant_id' => 1,
            'status' => ContractStatus::Draft,
        ]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/contracts/{$draftContract->id}/invoices", [
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_access_other_tenant_contract(): void
    {
        $otherUser = User::factory()->create(['tenant_id' => 999]);
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/contracts/{$this->contract->id}/invoices");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson("/api/contracts/{$this->contract->id}/invoices");

        $response->assertStatus(401);
    }
}

<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 5000);
        $taxAmount = round($subtotal * 0.175, 2);
        $total = $subtotal + $taxAmount;
        return [
            'contract_id' => Contract::factory(),
            'tenant_id' => fn (array $attr) => Contract::find($attr['contract_id'])->tenant_id,
            'invoice_number' => 'INV-' . fake()->numerify('###') . '-' . now()->format('Ym') . '-' . fake()->unique()->numerify('####'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'status' => InvoiceStatus::Pending,
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'paid_at' => null,
        ];
    }
}

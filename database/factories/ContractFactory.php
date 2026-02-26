<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');
        $end = fake()->dateTimeBetween($start, '+2 years');
        return [
            'tenant_id' => fake()->numberBetween(1, 999),
            'unit_name' => 'Unit ' . fake()->buildingNumber(),
            'customer_name' => fake()->name(),
            'rent_amount' => fake()->randomFloat(2, 500, 5000),
            'start_date' => $start,
            'end_date' => $end,
            'status' => fake()->randomElement(ContractStatus::cases()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ContractStatus::Active]);
    }
}

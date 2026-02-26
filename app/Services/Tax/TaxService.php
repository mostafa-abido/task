<?php

namespace App\Services\Tax;

class TaxService
{
    /**
     * Create a new tax service instance.
     *
     * @param  array<TaxCalculatorInterface>  $calculators
     */
    public function __construct(
        private readonly array $calculators
    ) {
    }

    /**
     * Calculate the total tax for the given amount using all registered calculators.
     */
    public function calculateTotalTax(float $amount): float
    {
        $total = 0.0;
        foreach ($this->calculators as $calculator) {
            $total += $calculator->calculate($amount);
        }
        return round($total, 2);
    }
}

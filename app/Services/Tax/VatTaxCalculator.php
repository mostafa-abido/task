<?php

namespace App\Services\Tax;

class VatTaxCalculator implements TaxCalculatorInterface
{
    /**
     * Calculate VAT (15%) for the given amount.
     */
    public function calculate(float $amount): float
    {
        return round($amount * 0.15, 2);
    }
}

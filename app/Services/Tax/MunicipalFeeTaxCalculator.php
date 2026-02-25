<?php

namespace App\Services\Tax;

class MunicipalFeeTaxCalculator implements TaxCalculatorInterface
{
    /**
     * Calculate municipal fee (2.5%) for the given amount.
     */
    public function calculate(float $amount): float
    {
        return round($amount * 0.025, 2);
    }
}

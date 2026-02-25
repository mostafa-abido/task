<?php

namespace App\Services\Tax;

interface TaxCalculatorInterface
{
    /**
     * Calculate the tax amount for the given amount.
     */
    public function calculate(float $amount): float;
}

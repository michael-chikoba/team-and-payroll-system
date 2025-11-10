<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TaxConfiguration;

class TaxCalculationService
{
    public function calculateTax(float $income, Employee $employee): float
    {
        // Get tax configuration for employee's location
        $taxConfig = TaxConfiguration::active()->first();
        
        if (!$taxConfig) {
            return 0.0;
        }

        // Calculate income tax
        $incomeTax = $taxConfig->calculateTax($income);
        
        // Calculate social security and medicare
        $socialSecurity = $income * ($taxConfig->social_security_rate / 100);
        $medicare = $income * ($taxConfig->medicare_rate / 100);
        
        return $incomeTax + $socialSecurity + $medicare;
    }

    public function getTaxBrackets(): array
    {
        $taxConfig = TaxConfiguration::active()->first();
        
        return $taxConfig ? $taxConfig->tax_brackets : [];
    }
}
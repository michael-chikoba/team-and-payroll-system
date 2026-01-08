<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TaxConfiguration;
use Illuminate\Support\Facades\Log;

class TaxCalculationService
{
    /**
     * Get tax configuration for employee
     */
    public function getTaxConfig(Employee $employee): ?TaxConfiguration
    {
        $businessId = $employee->business_id;
        $countryCode = $employee->getCountryCode();
        
        Log::info('Getting tax config for employee', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name,
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'employee_country_id' => $employee->country_id
        ]);
        
        $taxConfig = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);
        
        if (!$taxConfig) {
            Log::error('No tax configuration found for employee', [
                'employee_id' => $employee->id,
                'business_id' => $businessId,
                'country_code' => $countryCode,
                'suggestion' => 'Please create a tax configuration for this country/business'
            ]);
        }
        
        return $taxConfig;
    }
    
    /**
     * Calculate complete payroll for an employee
     */
    public function calculateEmployeePayroll(
        Employee $employee,
        float $overtimePay = 0,
        float $bonuses = 0,
        array $adjustments = []
    ): array
    {
        $taxConfig = $this->getTaxConfig($employee);
        
        if (!$taxConfig) {
            throw new \Exception('No tax configuration found for employee');
        }
        
        $basicSalary = (float) $employee->base_salary;
        $configData = $taxConfig->config_data;
        
        // 1. Calculate Allowances
        $allowances = $this->calculateAllowances($employee, $taxConfig);
        
        // 2. Gross Salary
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;
        
        // 3. Apply adjustments
        if (!empty($adjustments)) {
            $additionalBonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
            $additionalDeductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);
            
            $grossSalary += $additionalBonuses;
            $bonuses += $additionalBonuses;
        }
        
        // 4. Calculate Statutory Deductions (Dynamic)
        $statutory = $taxConfig->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);
        
        // 5. Calculate PAYE
        $taxableIncome = $grossSalary;
        
        // Reduce taxable income by pension contributions (if any)
        foreach ($statutory['breakdown'] as $deduction) {
            if ($deduction['type'] === 'pension') {
                $taxableIncome -= $deduction['amount'];
            }
        }
        
        $paye = $taxConfig->calculatePAYE($taxableIncome);
        
        // 6. Total Deductions
        $otherDeductions = ($adjustments['loan_deductions'] ?? 0) + 
                          ($adjustments['advance_deductions'] ?? 0) + 
                          ($adjustments['other_deductions'] ?? 0);
        
        $totalDeductions = $paye + $statutory['total_employee'] + $otherDeductions;
        
        // 7. Net Pay
        $netPay = $grossSalary - $totalDeductions;
        
        // 8. Prepare breakdown
        $breakdown = [
            'calculation_method' => 'tax_config_based',
            'tax_config_id' => $taxConfig->id,
            'tax_config_type' => $this->getConfigType($taxConfig),
            'tax_config_country' => $taxConfig->country_code ?? 'global',
            'tax_config_business_id' => $taxConfig->business_id ?? 'all',
            'employee_country_code' => $employee->getCountryCode(),
            'earnings_breakdown' => [
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'overtime_pay' => $overtimePay,
                'bonuses' => $bonuses,
                'gross_total' => $grossSalary,
            ],
            'deductions_breakdown' => [
                'paye' => $paye,
                'statutory_breakdown' => $statutory['breakdown'],
                'statutory_total' => $statutory['total_employee'],
                'employer_total' => $statutory['total_employer'],
                'other_deductions' => $otherDeductions,
                'total_deductions' => $totalDeductions,
            ],
            'net_calculation' => [
                'gross_salary' => $grossSalary,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
            ],
            'adjustments' => $adjustments,
        ];
        
        return [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'gross_salary' => $grossSalary,
            'deductions' => [
                'paye' => $paye,
                'statutory' => $statutory['breakdown'],
                'statutory_total' => $statutory['total_employee'],
                'employer_total' => $statutory['total_employer'],
                'other_deductions' => $otherDeductions,
                'total_deductions' => $totalDeductions,
            ],
            'net_pay' => $netPay,
            'breakdown' => $breakdown,
            'tax_config' => [
                'id' => $taxConfig->id,
                'country_code' => $taxConfig->country_code,
                'business_id' => $taxConfig->business_id,
                'config_data' => $configData,
            ],
        ];
    }
    
    /**
     * Calculate allowances based on tax config
     */
    private function calculateAllowances(Employee $employee, TaxConfiguration $taxConfig): array
    {
        $basicSalary = (float) $employee->base_salary;
        $config = $taxConfig->config_data;
        
        // Housing allowance (if enabled)
        $housing = 0;
        if (!empty($config['includeHousingAllowance']) && $config['includeHousingAllowance'] === true) {
            $housing = $basicSalary * 0.25;
        }
        
        // Get transport and lunch from employee model
        $transport = (float) ($employee->transport_allowance ?? 0);
        $lunch = (float) ($employee->lunch_allowance ?? 0);
        
        // Apply rounding from tax config
        $roundingMethod = $config['roundingMethod'] ?? 'nearest';
        
        return [
            'housing' => $this->applyRounding($housing, $roundingMethod),
            'transport' => $this->applyRounding($transport, $roundingMethod),
            'lunch' => $this->applyRounding($lunch, $roundingMethod),
            'total' => $this->applyRounding($housing + $transport + $lunch, $roundingMethod),
        ];
    }
    
    /**
     * Get tax config type
     */
    private function getConfigType(TaxConfiguration $taxConfig): string
    {
        if ($taxConfig->business_id && $taxConfig->country_code) {
            return 'business-country-specific';
        } elseif ($taxConfig->business_id) {
            return 'business-specific';
        } elseif ($taxConfig->country_code) {
            return 'country-specific';
        }
        return 'global';
    }
    
    /**
     * Apply rounding
     */
    private function applyRounding(float $amount, string $method): float
    {
        return match($method) {
            'up' => ceil($amount),
            'down' => floor($amount),
            'none' => $amount,
            default => round($amount, 2),
        };
    }
}
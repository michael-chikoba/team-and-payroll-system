<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\TaxConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayrollCalculationService
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

  

   
public function generatePayslipPreview(Employee $employee, Payroll $payroll): array
    {
        // Load relationships
        $employee->load(['country', 'business']);
        
        $taxConfig = $this->getTaxConfigForEmployee($employee);
        
        if (!$taxConfig) {
            throw new \Exception('No active tax configuration found for employee');
        }

        $this->logTaxConfigUsage($employee, $taxConfig, 'preview');

        return $this->calculatePayslipData($employee, $payroll, $taxConfig, 'pending');
    }

    /**
     * Create and save payslip with full calculations
     */
    public function createPayslip(Employee $employee, Payroll $payroll, string $status = 'pending'): ?Payslip
    {
        return $this->createPayslipWithAdjustments($employee, $payroll, $status, []);
    }

    /**
     * Create payslip with adjustments
     */
    public function createPayslipWithAdjustments(
        Employee $employee,
        Payroll $payroll,
        string $status = 'pending',
        array $adjustments = []
    ): ?Payslip {
        try {
            // Load necessary relationships
            $employee->load(['country', 'business']);
            
            $taxConfig = $this->getTaxConfigForEmployee($employee);
            
            if (!$taxConfig) {
                Log::error('No tax configuration found', [
                    'employee_id' => $employee->id,
                    'business_id' => $employee->business_id,
                    'country_code' => $employee->getCountryCode()
                ]);
                throw new \Exception('No active tax configuration found for employee');
            }

            $this->logTaxConfigUsage($employee, $taxConfig, 'creation');

            $calculationData = $this->calculatePayslipData($employee, $payroll, $taxConfig, $status);
            
            // Apply adjustments
            $bonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
            $additionalDeductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);
            
            // Update calculation data with adjustments
            if ($bonuses > 0 || $additionalDeductions > 0) {
                $calculationData['bonuses'] += $bonuses;
                $calculationData['gross_salary'] += $bonuses;
                $calculationData['deductions']['other_deductions'] += $additionalDeductions;
                $calculationData['deductions']['total_deductions'] += $additionalDeductions;
                $calculationData['net_pay'] = $calculationData['gross_salary'] - $calculationData['deductions']['total_deductions'];
                
                // Update breakdown with adjustments
                $calculationData['breakdown']['adjustments'] = $adjustments;
                $calculationData['breakdown']['adjustments_applied'] = [
                    'total_bonuses' => $bonuses,
                    'total_additional_deductions' => $additionalDeductions,
                    'net_effect' => $bonuses - $additionalDeductions
                ];
            }

            // Map dynamic deductions to legacy columns for backward compatibility
            $statutory = collect($calculationData['deductions']['statutory_breakdown']);
            
            $napsaAmount = $statutory->where('type', 'levy')
                ->filter(fn($i) => stripos($i['name'], 'NAPSA') !== false)
                ->sum('amount');
            
            $nhimaAmount = $statutory->where('type', 'health')
                ->filter(fn($i) => stripos($i['name'], 'NHIMA') !== false || stripos($i['name'], 'NHIF') !== false)
                ->sum('amount');
            
            $pensionAmount = $statutory->where('type', 'pension')
                ->filter(fn($i) => stripos($i['name'], 'NAPSA') === false)
                ->sum('amount');

            Log::debug('Creating payslip', [
                'employee_id' => $employee->id,
                'payroll_id' => $payroll->id,
                'basic_salary' => $calculationData['basic_salary'],
                'gross_salary' => $calculationData['gross_salary'],
                'net_pay' => $calculationData['net_pay'],
                'napsa' => $napsaAmount,
                'nhima' => $nhimaAmount,
                'pension' => $pensionAmount
            ]);

            return Payslip::create([
                'employee_id' => $employee->id,
                'payroll_id' => $payroll->id,
                'pay_period_start' => $payroll->start_date,
                'pay_period_end' => $payroll->end_date,
                'payment_date' => $payroll->end_date,
                'basic_salary' => $calculationData['basic_salary'],
                'house_allowance' => $calculationData['allowances']['housing'],
                'transport_allowance' => $calculationData['allowances']['transport'],
                'other_allowances' => $calculationData['allowances']['lunch'],
                'overtime_hours' => $calculationData['overtime_hours'],
                'overtime_rate' => $calculationData['overtime_rate'],
                'overtime_pay' => $calculationData['overtime_pay'],
                'bonuses' => $calculationData['bonuses'],
                'gross_salary' => $calculationData['gross_salary'],
                
                // Legacy columns populated from dynamic data
                'napsa' => $napsaAmount,
                'nhima' => $nhimaAmount,
                'pension' => $pensionAmount,
                
                'paye' => $calculationData['deductions']['paye'],
                'other_deductions' => $calculationData['deductions']['other_deductions'],
                'total_deductions' => $calculationData['deductions']['total_deductions'],
                'net_pay' => $calculationData['net_pay'],
                'status' => $status,
                'breakdown' => $calculationData['breakdown'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create payslip for employee {$employee->id}: " . $e->getMessage(), [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->first_name ?? 'Unknown',
                'business_id' => $employee->business_id,
                'country_code' => $employee->getCountryCode(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get appropriate tax configuration for employee
     */
    private function getTaxConfigForEmployee(Employee $employee): ?TaxConfiguration
    {
        $businessId = $employee->business_id;
        $countryCode = $employee->getCountryCode();

        Log::info("Looking up tax configuration", [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->first_name ?? 'Unknown',
            'business_id' => $businessId,
            'country_id' => $employee->country_id,
            'country_code' => $countryCode,
            'business_country_code' => $employee->business->country_code ?? null
        ]);

        if (!$countryCode) {
            Log::warning("Employee has no country code", [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->first_name ?? 'Unknown',
                'business_id' => $businessId
            ]);
        }

        $config = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);
        
        if (!$config) {
            Log::error("No tax configuration found for employee", [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->first_name ?? 'Unknown',
                'business_id' => $businessId,
                'business_name' => $employee->business->name ?? 'Unknown',
                'country_code' => $countryCode ?? 'Not set'
            ]);
        }
        
        return $config;
    }

    /**
     * Log which tax configuration is being used
     */
    private function logTaxConfigUsage(Employee $employee, TaxConfiguration $taxConfig, string $operation): void
    {
        $configType = 'global';
        if ($taxConfig->business_id && $taxConfig->country_code) {
            $configType = 'business-specific';
        } elseif ($taxConfig->country_code) {
            $configType = 'country-specific';
        }

        Log::info("Tax configuration applied", [
            'operation' => $operation,
            'employee_id' => $employee->id,
            'employee_name' => ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''),
            'business_id' => $employee->business_id,
            'business_name' => $employee->business->name ?? 'Unknown',
            'employee_country_id' => $employee->country_id,
            'employee_country_code' => $employee->getCountryCode(),
            'tax_config_id' => $taxConfig->id,
            'tax_config_type' => $configType,
            'tax_config_country' => $taxConfig->country_code ?? 'global',
            'tax_config_business_id' => $taxConfig->business_id ?? 'all',
            'currency' => $taxConfig->getCurrency(),
            'rounding_method' => $taxConfig->config_data['roundingMethod'] ?? 'nearest',
            'statutory_deductions_count' => count($taxConfig->config_data['statutory_deductions'] ?? [])
        ]);
    }

    /**
 * Calculate complete payslip data using tax configuration
 */
private function calculatePayslipData(Employee $employee, Payroll $payroll, TaxConfiguration $taxConfig, string $status): array
{
    try {
        // 1. Basic Salary from employee record
        $basicSalary = (float) $employee->base_salary;
        
        if ($basicSalary <= 0) {
            throw new \InvalidArgumentException("Basic salary must be greater than 0");
        }
        
        // 2. Allowances from tax config and employee data
        $allowances = $taxConfig->calculateAllowances($employee);
        
        Log::debug("Allowances calculated", [
            'employee_id' => $employee->id,
            'basic_salary' => $basicSalary,
            'housing' => $allowances['housing'],
            'transport' => $allowances['transport'],
            'lunch' => $allowances['lunch'],
            'total' => $allowances['total']
        ]);
        
        // 3. Overtime
        $overtimeData = $this->calculateOvertimeData($employee, $payroll);
        
        // 4. Bonuses (can be adjusted later)
        $bonuses = 0.0;
        
        // 5. Gross Salary
        $grossSalary = $basicSalary + $allowances['total'] + $overtimeData['pay'] + $bonuses;
        
        // 6. Deductions using dynamic statutory deductions from config
        $deductions = $this->calculateDeductions($basicSalary, $grossSalary, $taxConfig, $employee);
        
        // 7. Net Pay
        $netPay = $grossSalary - $deductions['total_deductions'];
        
        // 8. Prepare Breakdown
        $breakdown = [
            'calculation_method' => 'Dynamic Tax Configuration',
            'tax_config_id' => $taxConfig->id,
            'tax_config_type' => $this->getTaxConfigType($taxConfig),
            'tax_config_country' => $taxConfig->country_code ?? 'global',
            'tax_config_business_id' => $taxConfig->business_id ?? 'all',
            'employee_country_code' => $employee->getCountryCode(),
            'currency' => $taxConfig->getCurrency(),
            'earnings_breakdown' => [
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'overtime' => $overtimeData,
                'bonuses' => $bonuses,
                'gross_total' => $grossSalary,
            ],
            'deductions_breakdown' => $deductions,
            'net_calculation' => [
                'gross_salary' => $grossSalary,
                'minus_deductions' => $deductions['total_deductions'],
                'equals_net_pay' => $netPay,
            ],
            'statutory_details' => $deductions['statutory_breakdown'] ?? [],
        ];
        
        return [
            // Flat data for DB creation
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'overtime_hours' => $overtimeData['hours'],
            'overtime_rate' => $overtimeData['rate'],
            'overtime_pay' => $overtimeData['pay'],
            'bonuses' => $bonuses,
            'gross_salary' => $grossSalary,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'breakdown' => $breakdown,
            'status' => $status,

            // Structured arrays required by Controller for Preview
            'earnings' => [
                'basic_salary' => $basicSalary,
                'house_allowance' => $allowances['housing'],
                'transport_allowance' => $allowances['transport'],
                'lunch_allowance' => $allowances['lunch'],
                'overtime_pay' => $overtimeData['pay'],
                'bonuses' => $bonuses,
                'gross_salary' => $grossSalary,
            ],
            'summary' => [
                'gross_pay' => $grossSalary,
                'total_deductions' => $deductions['total_deductions'],
                'net_pay' => $netPay,
            ]
        ];
        
    } catch (\Exception $e) {
        Log::error('Failed to calculate payslip data', [
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    }
}


/**
 * Determine tax config type
 */
private function getTaxConfigType(TaxConfiguration $taxConfig): string
{
    if ($taxConfig->business_id && $taxConfig->country_code) {
        return 'business-specific';
    } elseif ($taxConfig->country_code) {
        return 'country-specific';
    }
    return 'global';
}

/**
 * Calculate deductions using tax configuration
 */
private function calculateDeductions(
    float $basicSalary, 
    float $grossSalary, 
    TaxConfiguration $taxConfig, 
    Employee $employee
): array {
    // 1. Calculate Statutory Deductions (Dynamic from config)
    $statutory = $taxConfig->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);
    
    // 2. Determine Taxable Income (reduce by pension-type deductions)
    $taxableIncome = $grossSalary;
    
    foreach ($statutory['breakdown'] as $item) {
        if ($item['type'] === 'pension') {
            $taxableIncome -= $item['amount'];
        }
    }

    // 3. Calculate PAYE using tax bands from config
    $paye = $taxConfig->calculatePAYE($taxableIncome);
    
    // 4. Other deductions (can be added later)
    $otherDeductions = 0.0;
    
    // 5. Total deductions
    $totalDeductions = $paye + $statutory['total_employee'] + $otherDeductions;

    Log::debug("Deductions calculated", [
        'employee_id' => $employee->id,
        'basic_salary' => $basicSalary,
        'gross_salary' => $grossSalary,
        'taxable_income' => $taxableIncome,
        'paye' => $paye,
        'statutory_total' => $statutory['total_employee'],
        'statutory_count' => count($statutory['breakdown']),
        'total_deductions' => $totalDeductions
    ]);

    return [
        'paye' => $paye,
        'statutory_breakdown' => $statutory['breakdown'],
        'statutory_total' => $statutory['total_employee'],
        'employer_total' => $statutory['total_employer'],
        'other_deductions' => $otherDeductions,
        'total_deductions' => $totalDeductions,
    ];
}

/**
 * Calculate overtime data
 */
private function calculateOvertimeData(Employee $employee, Payroll $payroll): array
{
    $overtimeHours = $this->attendanceService->getOvertimeHours(
        $employee->id,
        $payroll->start_date,
        $payroll->end_date
    );
    
    // Standard 173.33 hours/month (40 hours/week * 52 weeks / 12 months)
    $hourlyRate = $employee->base_salary > 0 ? ($employee->base_salary / 173.33) : 0;
    $overtimeRate = $hourlyRate * 1.5; // 1.5x for overtime
    $overtimePay = $overtimeHours * $overtimeRate;

    return [
        'hours' => round($overtimeHours, 2),
        'rate' => round($overtimeRate, 2),
        'pay' => round($overtimePay, 2),
    ];
}

/**
 * Update payroll totals
 */
public function updatePayrollTotals(Payroll $payroll): void
{
    $payslips = $payroll->payslips()->get();
    
    $payroll->update([
        'total_gross' => $payslips->sum('gross_salary'),
        'total_net' => $payslips->sum('net_pay'),
        'employee_count' => $payslips->count(),
    ]);
    
    Log::info('Payroll totals updated', [
        'payroll_id' => $payroll->id,
        'total_gross' => $payroll->total_gross,
        'total_net' => $payroll->total_net,
        'employee_count' => $payroll->employee_count
    ]);
}

    /**
     * Calculate allowances - FIXED to properly retrieve from employee
     */
    private function calculateAllowances(Employee $employee, TaxConfiguration $taxConfig): array
    {
        $basicSalary = (float) $employee->base_salary;
        $config = $taxConfig->config_data;
        
        // Housing allowance (25% of basic if enabled in config)
        $housing = 0;
        if (!empty($config['includeHousingAllowance']) && $config['includeHousingAllowance'] === true) {
             $housing = $basicSalary * 0.25; 
        }

        // CRITICAL FIX: Get transport and lunch allowances directly from employee attributes
        // Make sure to cast to float and handle null values
        $transport = (float) ($employee->transport_allowance ?? 0.00);
        $lunch = (float) ($employee->lunch_allowance ?? 0.00);

        Log::debug("Allowances calculated", [
            'employee_id' => $employee->id,
            'basic_salary' => $basicSalary,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
            'lunch_allowance' => $lunch,
            'housing_enabled_in_config' => $config['includeHousingAllowance'] ?? false,
            'transport_from_db' => $employee->transport_allowance,
            'lunch_from_db' => $employee->lunch_allowance
        ]);

        return [
            'housing' => $this->applyRounding($housing, $config),
            'transport' => $this->applyRounding($transport, $config),
            'lunch' => $this->applyRounding($lunch, $config),
            'total' => $this->applyRounding($housing + $transport + $lunch, $config)
        ];
    }



    
    private function applyRounding(float $amount, array $config): float
    {
        $method = $config['roundingMethod'] ?? 'nearest';
        
        switch ($method) {
            case 'up': return ceil($amount);
            case 'down': return floor($amount);
            case 'none': return $amount;
            default: return round($amount, 2);
        }
    }
}
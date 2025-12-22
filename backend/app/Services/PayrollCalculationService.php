<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\TaxConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculationService
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Generate payslip preview without saving
     */
    public function generatePayslipPreview(Employee $employee, Payroll $payroll): array
    {
        $taxConfig = TaxConfiguration::active()->first();
        
        if (!$taxConfig) {
            throw new \Exception('No active tax configuration found');
        }

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
            $taxConfig = TaxConfiguration::active()->first();
            
            if (!$taxConfig) {
                throw new \Exception('No active tax configuration found');
            }

            $calculationData = $this->calculatePayslipData($employee, $payroll, $taxConfig, $status);
            
            // Apply adjustments
            $bonuses = ($adjustments['overtime_bonus'] ?? 0) + ($adjustments['other_bonuses'] ?? 0);
            $additionalDeductions = ($adjustments['loan_deductions'] ?? 0) + ($adjustments['advance_deductions'] ?? 0);
            
            // Update calculation data with adjustments
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

            // Map dynamic deductions to legacy columns for reporting compatibility
            $statutory = collect($calculationData['deductions']['statutory_breakdown']);
            
            // Search by name/type for legacy columns
            $napsaAmount = $statutory->filter(fn($i) => stripos($i['name'], 'NAPSA') !== false)->sum('amount');
            $nhimaAmount = $statutory->filter(fn($i) => stripos($i['name'], 'NHIMA') !== false)->sum('amount');
            $pensionAmount = $statutory->filter(fn($i) => $i['type'] === 'pension' && stripos($i['name'], 'NAPSA') === false)->sum('amount');

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
                'breakdown' => $calculationData['breakdown'], // Full dynamic breakdown stored here
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to create payslip for employee {$employee->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate complete payslip data using tax configuration
     */
    private function calculatePayslipData(Employee $employee, Payroll $payroll, TaxConfiguration $taxConfig, string $status): array
    {
        // 1. Basic Salary
        $basicSalary = (float) $employee->base_salary;
        
        // 2. Allowances
        $allowances = $this->calculateAllowances($employee, $taxConfig);
        
        // 3. Overtime
        $overtimeData = $this->calculateOvertimeData($employee, $payroll);
        
        // 4. Bonuses (Placeholder)
        $bonuses = 0.0;
        
        // 5. Gross Salary
        $grossSalary = $basicSalary + $allowances['total'] + $overtimeData['pay'] + $bonuses;
        
        // 6. Deductions (Dynamic Statutory + PAYE)
        $deductions = $this->calculateDeductions($basicSalary, $grossSalary, $taxConfig, $employee);
        
        // 7. Net Pay
        $netPay = $grossSalary - $deductions['total_deductions'];
        
        // 8. Prepare Breakdown
        $breakdown = [
            'calculation_method' => 'Dynamic Statutory Deductions',
            'tax_config_id' => $taxConfig->id,
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
            'statutory_details' => $deductions['statutory_breakdown'],
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

            // *** FIX: Structured arrays required by Controller for Preview ***
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
    }

    /**
     * Calculate allowances
     */
    private function calculateAllowances(Employee $employee, TaxConfiguration $taxConfig): array
    {
        $basicSalary = (float) $employee->base_salary;
        $config = $taxConfig->config_data;
        
        // Check if housing allowance logic is enabled in config
        $housing = 0;
        if (!empty($config['includeHousingAllowance']) && $config['includeHousingAllowance'] === true) {
             $housing = $basicSalary * 0.25; 
        }

        $transport = (float) $employee->transport_allowance;
        $lunch = (float) $employee->lunch_allowance;

        return [
            'housing' => $this->applyRounding($housing, $config),
            'transport' => $this->applyRounding($transport, $config),
            'lunch' => $this->applyRounding($lunch, $config),
            'total' => $this->applyRounding($housing + $transport + $lunch, $config)
        ];
    }

    /**
     * Calculate DEDUCTIONS (Dynamic Version)
     */
    private function calculateDeductions(float $basicSalary, float $grossSalary, TaxConfiguration $taxConfig, Employee $employee): array
    {
        $config = $taxConfig->config_data;
        
        // 1. Calculate Statutory Deductions (Dynamic Array)
        $statutory = $taxConfig->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);
        
        // 2. Determine Taxable Income
        // Assume pension/social security types reduce taxable income.
        $taxableIncome = $grossSalary;
        
        foreach($statutory['breakdown'] as $item) {
            if ($item['type'] === 'pension') {
                $taxableIncome -= $item['amount'];
            }
        }

        // 3. Calculate PAYE
        $paye = $taxConfig->calculatePAYE($taxableIncome);
        
        // 4. Totals
        $otherDeductions = 0;
        $totalDeductions = $paye + $statutory['total_employee'] + $otherDeductions;

        return [
            'paye' => $paye,
            'statutory_breakdown' => $statutory['breakdown'], 
            'statutory_total' => $statutory['total_employee'],
            'employer_total' => $statutory['total_employer'],
            'other_deductions' => $otherDeductions,
            'total_deductions' => $this->applyRounding($totalDeductions, $config),
        ];
    }

    private function calculateOvertimeData(Employee $employee, Payroll $payroll): array
    {
        $overtimeHours = $this->attendanceService->getOvertimeHours(
            $employee->id,
            $payroll->start_date,
            $payroll->end_date
        );
        
        // Standard 173.33 hours/month
        $hourlyRate = $employee->base_salary > 0 ? ($employee->base_salary / 173.33) : 0; 
        $overtimeRate = $hourlyRate * 1.5;
        $overtimePay = $overtimeHours * $overtimeRate;

        return [
            'hours' => $overtimeHours,
            'rate' => round($overtimeRate, 2),
            'pay' => round($overtimePay, 2),
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
    
    public function updatePayrollTotals(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->get();
        $payroll->update([
            'total_gross' => $payslips->sum('gross_salary'),
            'total_net' => $payslips->sum('net_pay'),
            'employee_count' => $payslips->count(),
        ]);
    }
}
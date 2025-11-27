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
     * Returns full calculation data as array
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
        try {
            $taxConfig = TaxConfiguration::active()->first();
            
            if (!$taxConfig) {
                throw new \Exception('No active tax configuration found');
            }

            $calculationData = $this->calculatePayslipData($employee, $payroll, $taxConfig, $status);
            
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
                'napsa' => $calculationData['deductions']['napsa'],
                'paye' => $calculationData['deductions']['paye'],
                'nhima' => $calculationData['deductions']['nhima'],
                'other_deductions' => $calculationData['deductions']['other_deductions'],
                'total_deductions' => $calculationData['deductions']['total_deductions'],
                'net_pay' => $calculationData['net_pay'],
                'status' => $status,
                'breakdown' => $calculationData['breakdown'],
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to create payslip for employee {$employee->id}: " . $e->getMessage());
            return null;
        }
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
                'napsa' => $calculationData['deductions']['napsa'],
                'paye' => $calculationData['deductions']['paye'],
                'nhima' => $calculationData['deductions']['nhima'],
                'other_deductions' => $calculationData['deductions']['other_deductions'],
                'total_deductions' => $calculationData['deductions']['total_deductions'],
                'net_pay' => $calculationData['net_pay'],
                'status' => $status,
                'breakdown' => $calculationData['breakdown'],
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to create payslip with adjustments for employee {$employee->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate complete payslip data using tax configuration
     * This is the core method for all calculations
     */
    private function calculatePayslipData(Employee $employee, Payroll $payroll, TaxConfiguration $taxConfig, string $status): array
    {
        // Step 1: Calculate basic salary
        $basicSalary = $this->calculateBasicSalary($employee, $payroll);
        
        // Step 2: Calculate allowances using tax configuration and employee data
        $allowances = $this->calculateAllowances($employee, $taxConfig);
        
        // Step 3: Calculate overtime
        $overtimeData = $this->calculateOvertimeData($employee, $payroll);
        
        // Step 4: Calculate bonuses
        $bonuses = $this->calculateBonuses($employee, $payroll);
        
        // Step 5: Calculate gross salary
        $grossSalary = $basicSalary + $allowances['total'] + $overtimeData['pay'] + $bonuses;
        
        // Step 6: Calculate deductions using tax configuration
        $deductions = $this->calculateDeductions($basicSalary, $grossSalary, $taxConfig);
        
        // Step 7: Calculate net pay
        $netPay = $grossSalary - $deductions['total_deductions'];
        
        // Step 8: Prepare detailed breakdown
        $breakdown = [
            'calculation_method' => 'Tax Configuration Based',
            'tax_config_id' => $taxConfig->id,
            'earnings_breakdown' => [
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'overtime' => [
                    'hours' => $overtimeData['hours'],
                    'rate' => $overtimeData['rate'],
                    'total' => $overtimeData['pay'],
                ],
                'bonuses' => $bonuses,
                'gross_total' => $grossSalary,
            ],
            'deductions_breakdown' => $deductions,
            'net_calculation' => [
                'gross_salary' => $grossSalary,
                'minus_deductions' => $deductions['total_deductions'],
                'equals_net_pay' => $netPay,
            ],
            'tax_rates_used' => [
                'housing_allowance_rate' => $taxConfig->config_data['housing_allowance_rate'] ?? 25,
                'napsa_rate' => $taxConfig->config_data['napsaRate'] ?? 5,
                'nhima_rate' => $taxConfig->config_data['nhimaEmployeeRate'] ?? 1,
            ],
        ];

        return [
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
            // Structured for response
            'earnings' => [
                'basic_salary' => $basicSalary,
                'house_allowance' => $allowances['housing'],
                'transport_allowance' => $allowances['transport'],
                'lunch_allowance' => $allowances['lunch'],
                'overtime_hours' => $overtimeData['hours'],
                'overtime_rate' => $overtimeData['rate'],
                'overtime_pay' => $overtimeData['pay'],
                'bonuses' => $bonuses,
                'gross_salary' => $grossSalary,
            ],
            'deductions' => [
                'paye' => $deductions['paye'],
                'napsa' => $deductions['napsa'],
                'nhima' => $deductions['nhima'],
                'other_deductions' => $deductions['other_deductions'],
                'total_deductions' => $deductions['total_deductions'],
            ],
            'summary' => [
                'gross_pay' => $grossSalary,
                'total_deductions' => $deductions['total_deductions'],
                'net_pay' => $netPay,
            ],
        ];
    }

    /**
     * Calculate allowances using tax configuration and employee data
     */
    private function calculateAllowances(Employee $employee, TaxConfiguration $taxConfig): array
    {
        $basicSalary = $employee->base_salary;
        $config = $taxConfig->config_data;
        
        $housingRate = $config['housing_allowance_rate'] ?? 25;
        $housing = $basicSalary * ($housingRate / 100);
        
        // Get transport and lunch allowances directly from employee data
        $transport = $employee->transport_allowance;
        $lunch = $employee->lunch_allowance;

        return [
            'housing' => $this->applyRounding($housing, $config),
            'transport' => $this->applyRounding($transport, $config),
            'lunch' => $this->applyRounding($lunch, $config),
            'total' => $this->applyRounding($housing + $transport + $lunch, $config)
        ];
    }

    /**
     * Calculate all deductions using tax configuration
     */
    private function calculateDeductions(float $basicSalary, float $grossSalary, TaxConfiguration $taxConfig): array
    {
        $config = $taxConfig->config_data;
        
        // Calculate PAYE on gross salary
        $paye = $this->calculatePAYE($grossSalary, $config);
        
        // Calculate NAPSA on gross salary
        $napsa = $this->calculateNAPSA($grossSalary, $config);
        
        // Calculate NHIMA on basic salary
        $nhima = $this->calculateNHIMA($basicSalary, $config);
        
        $otherDeductions = 0;
        $totalDeductions = $paye + $napsa + $nhima + $otherDeductions;

        return [
            'paye' => $paye,
            'napsa' => $napsa,
            'nhima' => $nhima,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'calculation_details' => [
                'paye_base' => $grossSalary,
                'napsa_base' => $grossSalary,
                'nhima_base' => $basicSalary,
            ],
        ];
    }

    /**
     * Calculate PAYE using progressive tax bands
     */
    private function calculatePAYE(float $taxableAmount, array $config): float
    {
        $taxBands = $config['taxBands'] ?? [];
        $tax = 0.0;
        $remaining = $taxableAmount;

        usort($taxBands, function($a, $b) {
            return ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0);
        });

        foreach ($taxBands as $band) {
            if ($remaining <= 0) break;
            
            $lower = $band['lowerLimit'] ?? 0;
            $upper = $band['upperLimit'] ?? null;
            $rate = ($band['rate'] ?? 0) / 100;

            if ($upper === null) {
                $tax += $remaining * $rate;
                break;
            }

            $bandWidth = $upper - $lower;
            $taxableInBand = min($remaining, max(0, $bandWidth));
            $tax += $taxableInBand * $rate;
            $remaining -= $taxableInBand;
        }
        
        return $this->applyRounding($tax, $config);
    }

    /**
     * Calculate NAPSA contribution
     */
    private function calculateNAPSA(float $grossSalary, array $config): float
    {
        $rate = ($config['napsaRate'] ?? 5) / 100;
        $maxSalary = $config['napsaMaxSalary'] ?? 34164.00;
        $maxContrib = 1708.20;
        
        $assessable = min($grossSalary, $maxSalary);
        $contrib = min($assessable * $rate, $maxContrib);
        
        return $this->applyRounding($contrib, $config);
    }

    /**
     * Calculate NHIMA contribution
     */
    private function calculateNHIMA(float $basicSalary, array $config): float
    {
        $rate = ($config['nhimaEmployeeRate'] ?? 1) / 100;
        $maxSalary = $config['nhimaMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($basicSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib, $config);
    }

    /**
     * Apply rounding method from configuration
     */
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

    /**
     * Calculate basic salary for the period
     */
    private function calculateBasicSalary(Employee $employee, Payroll $payroll): float
    {
        return (float) $employee->base_salary;
    }

    /**
     * Calculate overtime with hours and rate
     */
    private function calculateOvertimeData(Employee $employee, Payroll $payroll): array
    {
        $overtimeHours = $this->attendanceService->getOvertimeHours(
            $employee->id,
            $payroll->start_date,
            $payroll->end_date
        );
        
        // Calculate overtime rate (1.5x hourly rate)
        $hourlyRate = $employee->base_salary / 160; // Assuming 160 hours monthly
        $overtimeRate = $hourlyRate * 1.5;
        $overtimePay = $overtimeHours * $overtimeRate;

        return [
            'hours' => $overtimeHours,
            'rate' => $overtimeRate,
            'pay' => $overtimePay,
        ];
    }

    /**
     * Calculate bonuses for the period
     */
    private function calculateBonuses(Employee $employee, Payroll $payroll): float
    {
        // Implement bonus calculation logic as needed
        return 0.0;
    }

    /**
     * Update payroll totals from payslips
     */
    public function updatePayrollTotals(Payroll $payroll): void
    {
        $payslips = $payroll->payslips()->get();
        
        $totalGross = $payslips->sum('gross_salary');
        $totalNet = $payslips->sum('net_pay');
        $employeeCount = $payslips->count();

        $payroll->update([
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
            'employee_count' => $employeeCount,
        ]);
    }
}
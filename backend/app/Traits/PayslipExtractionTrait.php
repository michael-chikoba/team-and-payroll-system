<?php

namespace App\Traits;

use App\Models\Payslip;
use Illuminate\Support\Facades\Log;

/**
 * Helper methods for extracting earnings and deductions from payslips
 */
trait PayslipExtractionTrait
{
    /**
     * Extract earnings from payslip with comprehensive fallback logic
     */
    private function extractEarningsFromPayslip(Payslip $payslip): array
    {
        Log::info('EXTRACTION: Processing payslip earnings', [
            'payslip_id' => $payslip->id,
            'employee_id' => $payslip->employee_id,
            'has_breakdown' => !empty($payslip->earnings_breakdown),
            'gross_salary' => $payslip->gross_salary
        ]);

        // Method 1: Use model method (recommended)
        $earnings = $payslip->getStructuredEarnings();
        
        if (!empty($earnings)) {
            Log::info('EXTRACTION: Earnings extracted from structured method', [
                'count' => count($earnings),
                'total' => array_sum(array_column($earnings, 'amount'))
            ]);
            return $earnings;
        }

        // Method 2: Check JSON earnings_breakdown field
        if (!empty($payslip->earnings_breakdown)) {
            $data = is_string($payslip->earnings_breakdown)
                ? json_decode($payslip->earnings_breakdown, true)
                : $payslip->earnings_breakdown;
            
            if (is_array($data)) {
                $earnings = [];
                foreach ($data as $item) {
                    if (is_array($item) && isset($item['name']) && isset($item['amount'])) {
                        if ((float)$item['amount'] > 0) {
                            $earnings[] = [
                                'name' => $item['name'],
                                'amount' => (float)$item['amount'],
                                'type' => $item['type'] ?? 'allowance',
                                'description' => $item['description'] ?? $item['name']
                            ];
                        }
                    }
                }
                
                if (!empty($earnings)) {
                    Log::info('EXTRACTION: Earnings extracted from JSON breakdown', [
                        'count' => count($earnings)
                    ]);
                    return $earnings;
                }
            }
        }

        // Method 3: Check individual earning columns
        $earningFields = [
            'basic_salary' => ['name' => 'Basic Salary', 'type' => 'basic'],
            'housing_allowance' => ['name' => 'Housing Allowance', 'type' => 'allowance'],
            'transport_allowance' => ['name' => 'Transport Allowance', 'type' => 'allowance'],
            'lunch_allowance' => ['name' => 'Lunch Allowance', 'type' => 'allowance'],
            'medical_allowance' => ['name' => 'Medical Allowance', 'type' => 'allowance'],
            'overtime_allowance' => ['name' => 'Overtime', 'type' => 'overtime'],
            'bonus' => ['name' => 'Bonus', 'type' => 'bonus'],
            'commission' => ['name' => 'Commission', 'type' => 'commission'],
        ];

        $earnings = [];
        foreach ($earningFields as $field => $config) {
            $value = $payslip->$field ?? 0;
            if ($value > 0) {
                $earnings[] = [
                    'name' => $config['name'],
                    'amount' => (float)$value,
                    'type' => $config['type'],
                    'description' => $config['name']
                ];
                
                Log::debug("EXTRACTION: Found earning in field", [
                    'field' => $field,
                    'amount' => $value
                ]);
            }
        }

        if (!empty($earnings)) {
            Log::info('EXTRACTION: Earnings extracted from individual fields', [
                'count' => count($earnings)
            ]);
            return $earnings;
        }

        // Method 4: Last resort - use gross_salary as basic salary
        if ($payslip->gross_salary > 0) {
            Log::warning('EXTRACTION: Using gross_salary as fallback', [
                'gross_salary' => $payslip->gross_salary
            ]);
            
            return [[
                'name' => 'Basic Salary',
                'amount' => (float)$payslip->gross_salary,
                'type' => 'basic',
                'description' => 'Basic Salary (from gross)'
            ]];
        }

        Log::error('EXTRACTION: No earnings found for payslip', [
            'payslip_id' => $payslip->id
        ]);

        return [];
    }

    /**
     * Extract deductions from payslip with comprehensive fallback logic
     */
    private function extractDeductionsFromPayslip(Payslip $payslip): array
    {
        Log::info('EXTRACTION: Processing payslip deductions', [
            'payslip_id' => $payslip->id,
            'employee_id' => $payslip->employee_id,
            'has_breakdown' => !empty($payslip->deductions_breakdown),
            'total_deductions' => $payslip->total_deductions
        ]);

        // Method 1: Use model method (recommended)
        $deductions = $payslip->getStructuredDeductions();
        
        if (!empty($deductions)) {
            Log::info('EXTRACTION: Deductions extracted from structured method', [
                'count' => count($deductions),
                'total' => array_sum(array_column($deductions, 'amount'))
            ]);
            return $deductions;
        }

        // Method 2: Check JSON deductions_breakdown field
        if (!empty($payslip->deductions_breakdown)) {
            $data = is_string($payslip->deductions_breakdown)
                ? json_decode($payslip->deductions_breakdown, true)
                : $payslip->deductions_breakdown;
            
            if (is_array($data)) {
                $deductions = [];
                foreach ($data as $item) {
                    if (is_array($item) && isset($item['name']) && isset($item['amount'])) {
                        if ((float)$item['amount'] > 0) {
                            $deductions[] = [
                                'name' => $item['name'],
                                'amount' => (float)$item['amount'],
                                'type' => $item['type'] ?? 'statutory',
                                'description' => $item['description'] ?? $item['name']
                            ];
                        }
                    }
                }
                
                if (!empty($deductions)) {
                    Log::info('EXTRACTION: Deductions extracted from JSON breakdown', [
                        'count' => count($deductions)
                    ]);
                    return $deductions;
                }
            }
        }

        // Method 3: Check individual deduction columns
        $deductionFields = [
            'paye' => ['name' => 'PAYE Tax', 'type' => 'tax'],
            'napsa' => ['name' => 'NAPSA', 'type' => 'statutory'],
            'nhima' => ['name' => 'NHIMA', 'type' => 'statutory'],
            'pension' => ['name' => 'Pension', 'type' => 'pension'],
            'social_security' => ['name' => 'Social Security', 'type' => 'statutory'],
            'provident_fund' => ['name' => 'Provident Fund', 'type' => 'pension'],
            'health_insurance' => ['name' => 'Health Insurance', 'type' => 'health'],
            'union_dues' => ['name' => 'Union Dues', 'type' => 'voluntary'],
            'loan_deduction' => ['name' => 'Loan Deduction', 'type' => 'loan'],
            'advance_deduction' => ['name' => 'Advance Deduction', 'type' => 'advance'],
            'other_deductions' => ['name' => 'Other Deductions', 'type' => 'other'],
        ];

        $deductions = [];
        foreach ($deductionFields as $field => $config) {
            $value = $payslip->$field ?? 0;
            if ($value > 0) {
                $deductions[] = [
                    'name' => $config['name'],
                    'amount' => (float)$value,
                    'type' => $config['type'],
                    'description' => $config['name']
                ];
                
                Log::debug("EXTRACTION: Found deduction in field", [
                    'field' => $field,
                    'amount' => $value
                ]);
            }
        }

        if (!empty($deductions)) {
            Log::info('EXTRACTION: Deductions extracted from individual fields', [
                'count' => count($deductions)
            ]);
            return $deductions;
        }

        Log::warning('EXTRACTION: No deductions found for payslip', [
            'payslip_id' => $payslip->id,
            'total_deductions' => $payslip->total_deductions
        ]);

        return [];
    }
}
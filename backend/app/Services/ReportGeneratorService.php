<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Employee;
use App\Models\Business;
use App\Models\Country;
use App\Traits\PayslipExtractionTrait;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;


class ReportGeneratorService
{
    
    /**
     * Generate comprehensive payroll report with dynamic earnings and deductions
     */
    public function generatePayrollReport(array $filters = [])
    {
        Log::info('REPORT_SERVICE: Generating dynamic payroll report', [
            'filters' => $filters
        ]);
        
        // Build query with relationships
        $query = Payslip::with([
            'employee.user',
            'employee.business',
            'employee.country'
        ]);
        
        // Apply date filtering
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->where(function($q) use ($filters) {
                $q->where(function($subQ) use ($filters) {
                    $subQ->whereBetween('pay_period_start', [$filters['start_date'], $filters['end_date']]);
                })->orWhere(function($subQ) use ($filters) {
                    $subQ->whereBetween('pay_period_end', [$filters['start_date'], $filters['end_date']]);
                })->orWhere(function($subQ) use ($filters) {
                    $subQ->where('pay_period_start', '<=', $filters['start_date'])
                        ->where('pay_period_end', '>=', $filters['end_date']);
                });
            });
        }
        
        // Apply filters
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['employee_ids']) && !empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }
        
        if (isset($filters['business_id']) && !empty($filters['business_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('business_id', $filters['business_id']);
            });
        }
        
        if (isset($filters['country']) && !empty($filters['country'])) {
            if (is_numeric($filters['country'])) {
                $query->whereHas('employee', function ($q) use ($filters) {
                    $q->where('country_id', $filters['country']);
                });
            } else {
                $country = Country::where('code', $filters['country'])->first();
                if ($country) {
                    $query->whereHas('employee', function ($q) use ($country) {
                        $q->where('country_id', $country->id);
                    });
                }
            }
        }
        
        if (isset($filters['department']) && !empty($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }
        
        // Get payslips
        $payslips = $query->orderBy('pay_period_start', 'desc')->get();
        
        Log::info('REPORT_SERVICE: Payslips retrieved', [
            'count' => $payslips->count(),
            'sample_id' => $payslips->first()->id ?? null
        ]);
        
        // Initialize tracking variables
        $totals = [
            'gross_salary' => 0,
            'net_salary' => 0,
            'total_deductions' => 0,
            'total_paye_tax' => 0,
            'total_earnings' => 0,
        ];
        
        $globalEarningTypes = [];
        $globalDeductionTypes = [];
        $earningBreakdownForSummary = [];
        $deductionBreakdownForSummary = [];

        // Detect whether the result set spans multiple currencies
        $uniqueCurrencies = $payslips
            ->map(fn($p) => optional(optional($p->employee)->country)->currency)
            ->filter()
            ->unique()
            ->values();

        $isMultiCurrency = $uniqueCurrencies->count() > 1;
        
        // Process each payslip
        $formattedPayslips = $payslips->map(function ($payslip) use (
            &$totals,
            &$globalEarningTypes,
            &$globalDeductionTypes,
            &$earningBreakdownForSummary,
            &$deductionBreakdownForSummary
        ) {
            // Extract earnings and deductions using Payslip model methods
            $earningsList = $this->extractEarningsFromPayslip($payslip);
            $deductionsList = $this->extractDeductionsFromPayslip($payslip);
            
            Log::info('REPORT_SERVICE: Extracted payslip data', [
                'payslip_id' => $payslip->id,
                'earnings_count' => count($earningsList),
                'deductions_count' => count($deductionsList),
                'earnings_total' => array_sum(array_column($earningsList, 'amount')),
                'deductions_total' => array_sum(array_column($deductionsList, 'amount'))
            ]);
            
            // Calculate totals
            $totalEarnings = array_sum(array_column($earningsList, 'amount'));
            $totalDeductions = array_sum(array_column($deductionsList, 'amount'));
            
            $gross = $payslip->gross_salary ?? $totalEarnings;
            $net = $payslip->net_pay ?? ($gross - $totalDeductions);
            $payeTax = $payslip->paye ?? 0;
            
            // Update aggregates
            $totals['gross_salary'] += $gross;
            $totals['net_salary'] += $net;
            $totals['total_deductions'] += $totalDeductions;
            $totals['total_paye_tax'] += $payeTax;
            $totals['total_earnings'] += $totalEarnings;
            
            // Process earnings
            $earningsBreakdown = [];
            foreach ($earningsList as $earning) {
                $name = strtoupper($earning['name']);
                $amount = (float)$earning['amount'];
                $type = $earning['type'];
                
                $earningsBreakdown[$name] = $amount;
                
                // Track global totals
                if (!isset($globalEarningTypes[$name])) {
                    $globalEarningTypes[$name] = 0;
                }
                $globalEarningTypes[$name] += $amount;
                
                // Build summary breakdown
                if (!isset($earningBreakdownForSummary[$name])) {
                    $earningBreakdownForSummary[$name] = [
                        'name' => $earning['name'],
                        'type' => $type,
                        'description' => $earning['description'],
                        'total_amount' => 0,
                        'employee_count' => 0,
                    ];
                }
                $earningBreakdownForSummary[$name]['total_amount'] += $amount;
                $earningBreakdownForSummary[$name]['employee_count']++;
            }
            
            // Process deductions
            $deductionsBreakdown = [];
            foreach ($deductionsList as $deduction) {
                $name = strtoupper($deduction['name']);
                $amount = (float)$deduction['amount'];
                $type = $deduction['type'];
                
                $deductionsBreakdown[$name] = $amount;
                
                // Track global totals
                if (!isset($globalDeductionTypes[$name])) {
                    $globalDeductionTypes[$name] = 0;
                }
                $globalDeductionTypes[$name] += $amount;
                
                // Build summary breakdown
                if (!isset($deductionBreakdownForSummary[$name])) {
                    $deductionBreakdownForSummary[$name] = [
                        'name' => $deduction['name'],
                        'type' => $type,
                        'description' => $deduction['description'],
                        'total_amount' => 0,
                        'employee_count' => 0,
                    ];
                }
                $deductionBreakdownForSummary[$name]['total_amount'] += $amount;
                $deductionBreakdownForSummary[$name]['employee_count']++;
            }
            
            // Resolve currency strictly from the payslip's own employee/country — no fallback defaults
            $currency = null;
            $currencySymbol = null;
            if ($payslip->employee && $payslip->employee->country) {
                $currency = $payslip->employee->country->currency ?? null;
                $currencySymbol = $payslip->employee->country->currency_symbol ?? null;
            }
            
            return [
                'employee_name' => $payslip->employee->user
                    ? ($payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name)
                    : 'N/A',
                'employee_id' => $payslip->employee_id,
                'business' => $payslip->employee->business ? $payslip->employee->business->name : 'N/A',
                'country' => $payslip->employee->country ? $payslip->employee->country->name : 'N/A',
                'country_code' => $payslip->employee->country ? $payslip->employee->country->code : null,
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
                'department' => $payslip->employee->department ?? 'Unassigned',
                'pay_period' => Carbon::parse($payslip->pay_period_start)->format('M d') . ' - ' .
                               Carbon::parse($payslip->pay_period_end)->format('M d, Y'),
                'pay_period_start' => $payslip->pay_period_start,
                'pay_period_end' => $payslip->pay_period_end,
                'gross_salary' => $gross,
                'net_salary' => $net,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'paye_tax' => $payeTax,
                'earnings_breakdown' => $earningsBreakdown,
                'deductions_breakdown' => $deductionsBreakdown,
                'earnings' => $earningsList,
                'deductions' => $deductionsList,
                'status' => $payslip->status ?? 'N/A'
            ];
        });
        
        // Prepare headers
        $earningHeaders = array_keys($globalEarningTypes);
        $deductionHeaders = array_keys($globalDeductionTypes);
        sort($earningHeaders);
        sort($deductionHeaders);
        
        // Calculate averages
        $processedCount = $payslips->count();
        $averageGross = $processedCount > 0 ? $totals['gross_salary'] / $processedCount : 0;
        $averageNet = $processedCount > 0 ? $totals['net_salary'] / $processedCount : 0;
        
        // Build filter info (may contain currency if a single country filter was applied)
        $filterInfo = $this->buildFilterInfo($filters);

        // Resolve report-level currency:
        // - Use the country filter's currency when a single country is explicitly filtered.
        // - Fall back to the first payslip's country only when ALL payslips share one currency.
        // - Leave null for multi-currency result sets — never invent a value.
        $reportCurrency = null;
        $reportCurrencySymbol = null;

        if (!empty($filterInfo['currency'])) {
            // A country filter was applied — currency is unambiguous
            $reportCurrency = $filterInfo['currency'];
            $reportCurrencySymbol = $filterInfo['currency_symbol'] ?? null;
        } elseif (!$isMultiCurrency && $uniqueCurrencies->isNotEmpty()) {
            // All payslips share the same currency — safe to surface it
            $firstWithCountry = $payslips->first(
                fn($p) => optional(optional($p->employee)->country)->currency !== null
            );
            if ($firstWithCountry) {
                $reportCurrency = $firstWithCountry->employee->country->currency;
                $reportCurrencySymbol = $firstWithCountry->employee->country->currency_symbol ?? null;
            }
        }
        // else: multi-currency or no country data — both remain null

        Log::info('REPORT_SERVICE: Report generation complete', [
            'processed_employees' => $processedCount,
            'earning_types' => count($earningHeaders),
            'deduction_types' => count($deductionHeaders),
            'total_earnings' => $totals['total_earnings'],
            'total_deductions' => $totals['total_deductions'],
            'is_multi_currency' => $isMultiCurrency,
            'report_currency' => $reportCurrency,
        ]);
        
        return [
            'data' => $formattedPayslips,
            'summary' => [
                'processed_employees' => $processedCount,
                'total_gross_salary' => $totals['gross_salary'],
                'total_net_salary' => $totals['net_salary'],
                'total_earnings' => $totals['total_earnings'],
                'total_all_deductions' => $totals['total_deductions'],
                'total_paye_tax' => $totals['total_paye_tax'],
                'average_gross_salary' => $averageGross,
                'average_net_salary' => $averageNet,
                'earning_totals' => $globalEarningTypes,
                'deduction_totals' => $globalDeductionTypes,
                'earning_headers' => $earningHeaders,
                'deduction_headers' => $deductionHeaders,
                'earning_breakdown' => array_values($earningBreakdownForSummary),
                'deduction_breakdown' => array_values($deductionBreakdownForSummary),
                'department_breakdown' => $this->calculateDepartmentBreakdown($formattedPayslips),
                'filters' => $filterInfo,
                'is_multi_currency' => $isMultiCurrency,
                'currency' => $reportCurrency,
                'currency_symbol' => $reportCurrencySymbol,
            ],
            'generated_at' => now(),
        ];
    }

    /**
     * Extract earnings from payslip using the model's built-in method
     */
    private function extractEarningsFromPayslip(Payslip $payslip): array
    {
        Log::info('EXTRACTION: Processing payslip earnings', [
            'payslip_id' => $payslip->id,
            'employee_id' => $payslip->employee_id,
            'gross_salary' => $payslip->gross_salary
        ]);

        // Use the Payslip model's earnings_breakdown attribute
        $earningsBreakdown = $payslip->earnings_breakdown;
        $earnings = [];
        
        // Extract basic salary
        if (!empty($earningsBreakdown['basic_salary']) && $earningsBreakdown['basic_salary'] > 0) {
            $earnings[] = [
                'name' => 'Basic Salary',
                'amount' => (float)$earningsBreakdown['basic_salary'],
                'type' => 'basic',
                'description' => 'Basic Salary'
            ];
        }
        
        // Extract allowances
        if (!empty($earningsBreakdown['allowances'])) {
            $allowances = $earningsBreakdown['allowances'];
            
            if (!empty($allowances['housing']) && $allowances['housing'] > 0) {
                $earnings[] = [
                    'name' => 'Housing Allowance',
                    'amount' => (float)$allowances['housing'],
                    'type' => 'allowance',
                    'description' => 'Housing Allowance'
                ];
            }
            
            if (!empty($allowances['transport']) && $allowances['transport'] > 0) {
                $earnings[] = [
                    'name' => 'Transport Allowance',
                    'amount' => (float)$allowances['transport'],
                    'type' => 'allowance',
                    'description' => 'Transport Allowance'
                ];
            }
            
            if (!empty($allowances['lunch']) && $allowances['lunch'] > 0) {
                $earnings[] = [
                    'name' => 'Lunch Allowance',
                    'amount' => (float)$allowances['lunch'],
                    'type' => 'allowance',
                    'description' => 'Lunch/Other Allowance'
                ];
            }
        }
        
        // Extract overtime
        if (!empty($earningsBreakdown['overtime']['pay']) && $earningsBreakdown['overtime']['pay'] > 0) {
            $earnings[] = [
                'name' => 'Overtime',
                'amount' => (float)$earningsBreakdown['overtime']['pay'],
                'type' => 'overtime',
                'description' => 'Overtime Pay'
            ];
        }
        
        // Extract bonuses
        if (!empty($earningsBreakdown['bonuses']) && $earningsBreakdown['bonuses'] > 0) {
            $earnings[] = [
                'name' => 'Bonus',
                'amount' => (float)$earningsBreakdown['bonuses'],
                'type' => 'bonus',
                'description' => 'Bonus'
            ];
        }
        
        Log::info('EXTRACTION: Earnings extracted', [
            'payslip_id' => $payslip->id,
            'count' => count($earnings),
            'total' => array_sum(array_column($earnings, 'amount'))
        ]);
        
        return $earnings;
    }

    /**
     * Extract deductions from payslip using the model's built-in method
     */
    private function extractDeductionsFromPayslip(Payslip $payslip): array
    {
        Log::info('EXTRACTION: Processing payslip deductions', [
            'payslip_id' => $payslip->id,
            'employee_id' => $payslip->employee_id,
            'total_deductions' => $payslip->total_deductions
        ]);

        // Use the Payslip model's getAllDeductionsFlat method
        $deductionsFlat = $payslip->getAllDeductionsFlat();
        $deductions = [];
        
        foreach ($deductionsFlat as $name => $deductionData) {
            if ($deductionData['amount'] > 0) {
                $deductions[] = [
                    'name' => $deductionData['name'],
                    'amount' => (float)$deductionData['amount'],
                    'type' => $deductionData['type'],
                    'description' => $deductionData['name']
                ];
            }
        }
        
        Log::info('EXTRACTION: Deductions extracted', [
            'payslip_id' => $payslip->id,
            'count' => count($deductions),
            'total' => array_sum(array_column($deductions, 'amount'))
        ]);
        
        return $deductions;
    }

    /**
     * Generate earnings-only report
     */
    public function generateEarningsReport(array $filters = [])
    {
        Log::info('REPORT_SERVICE: Generating earnings report', ['filters' => $filters]);
        
        // Get the full payroll report first
        $payrollReport = $this->generatePayrollReport($filters);
        
        // Extract only earnings-related data
        $earningsData = $payrollReport['data']->map(function ($payslip) {
            return [
                'employee_name' => $payslip['employee_name'],
                'employee_id' => $payslip['employee_id'],
                'business' => $payslip['business'],
                'country' => $payslip['country'],
                'country_code' => $payslip['country_code'],
                'currency' => $payslip['currency'],
                'currency_symbol' => $payslip['currency_symbol'],
                'department' => $payslip['department'],
                'pay_period' => $payslip['pay_period'],
                'pay_period_start' => $payslip['pay_period_start'],
                'pay_period_end' => $payslip['pay_period_end'],
                'total_earnings' => $payslip['total_earnings'],
                'gross_salary' => $payslip['gross_salary'],
                'earnings_breakdown' => $payslip['earnings_breakdown'],
                'earnings' => $payslip['earnings'],
                'status' => $payslip['status']
            ];
        });
        
        return [
            'data' => $earningsData,
            'summary' => [
                'processed_employees' => $payrollReport['summary']['processed_employees'],
                'total_earnings' => $payrollReport['summary']['total_earnings'],
                'total_gross_salary' => $payrollReport['summary']['total_gross_salary'],
                'average_earnings' => $payrollReport['summary']['processed_employees'] > 0 ?
                    $payrollReport['summary']['total_earnings'] / $payrollReport['summary']['processed_employees'] : 0,
                'earning_totals' => $payrollReport['summary']['earning_totals'],
                'earning_headers' => $payrollReport['summary']['earning_headers'],
                'earning_breakdown' => $payrollReport['summary']['earning_breakdown'],
                'department_breakdown' => $this->calculateDepartmentEarningsBreakdown($earningsData),
                'filters' => $payrollReport['summary']['filters'],
                'is_multi_currency' => $payrollReport['summary']['is_multi_currency'],
                'currency' => $payrollReport['summary']['currency'],
                'currency_symbol' => $payrollReport['summary']['currency_symbol'],
            ],
            'generated_at' => now(),
        ];
    }
    
    /**
     * Generate deductions-only report
     */
    public function generateDeductionsReport(array $filters = [])
    {
        Log::info('REPORT_SERVICE: Generating deductions report', ['filters' => $filters]);
        
        // Get the full payroll report first
        $payrollReport = $this->generatePayrollReport($filters);
        
        // Extract only deductions-related data
        $deductionsData = $payrollReport['data']->map(function ($payslip) {
            return [
                'employee_name' => $payslip['employee_name'],
                'employee_id' => $payslip['employee_id'],
                'business' => $payslip['business'],
                'country' => $payslip['country'],
                'country_code' => $payslip['country_code'],
                'currency' => $payslip['currency'],
                'currency_symbol' => $payslip['currency_symbol'],
                'department' => $payslip['department'],
                'pay_period' => $payslip['pay_period'],
                'pay_period_start' => $payslip['pay_period_start'],
                'pay_period_end' => $payslip['pay_period_end'],
                'total_deductions' => $payslip['total_deductions'],
                'paye_tax' => $payslip['paye_tax'],
                'deductions_breakdown' => $payslip['deductions_breakdown'],
                'deductions' => $payslip['deductions'],
                'net_salary' => $payslip['net_salary'],
                'status' => $payslip['status']
            ];
        });
        
        return [
            'data' => $deductionsData,
            'summary' => [
                'processed_employees' => $payrollReport['summary']['processed_employees'],
                'total_deductions' => $payrollReport['summary']['total_all_deductions'],
                'total_paye_tax' => $payrollReport['summary']['total_paye_tax'],
                'average_deductions' => $payrollReport['summary']['processed_employees'] > 0 ?
                    $payrollReport['summary']['total_all_deductions'] / $payrollReport['summary']['processed_employees'] : 0,
                'deduction_totals' => $payrollReport['summary']['deduction_totals'],
                'deduction_headers' => $payrollReport['summary']['deduction_headers'],
                'deduction_breakdown' => $payrollReport['summary']['deduction_breakdown'],
                'department_breakdown' => $this->calculateDepartmentDeductionsBreakdown($deductionsData),
                'filters' => $payrollReport['summary']['filters'],
                'is_multi_currency' => $payrollReport['summary']['is_multi_currency'],
                'currency' => $payrollReport['summary']['currency'],
                'currency_symbol' => $payrollReport['summary']['currency_symbol'],
            ],
            'generated_at' => now(),
        ];
    }

    /**
     * Helper: Calculate department breakdown
     */
    private function calculateDepartmentBreakdown($payslips): array
    {
        $breakdown = [];
        
        foreach ($payslips as $payslip) {
            $dept = $payslip['department'] ?? 'Unassigned';
            
            if (!isset($breakdown[$dept])) {
                $breakdown[$dept] = [
                    'employee_count' => 0,
                    'total_gross_salary' => 0,
                    'total_net_salary' => 0,
                    'total_deductions' => 0,
                    'total_earnings' => 0,
                ];
            }
            
            $breakdown[$dept]['employee_count']++;
            $breakdown[$dept]['total_gross_salary'] += $payslip['gross_salary'];
            $breakdown[$dept]['total_net_salary'] += $payslip['net_salary'];
            $breakdown[$dept]['total_deductions'] += $payslip['total_deductions'];
            $breakdown[$dept]['total_earnings'] += $payslip['total_earnings'];
        }
        
        return $breakdown;
    }
    
    /**
     * Calculate department earnings breakdown
     */
    private function calculateDepartmentEarningsBreakdown($earningsData): array
    {
        $departmentBreakdown = [];
        
        foreach ($earningsData as $data) {
            $department = $data['department'] ?? 'Unassigned';
            
            if (!isset($departmentBreakdown[$department])) {
                $departmentBreakdown[$department] = [
                    'employee_count' => 0,
                    'total_earnings' => 0,
                    'total_gross_salary' => 0,
                ];
            }
            
            $departmentBreakdown[$department]['employee_count']++;
            $departmentBreakdown[$department]['total_earnings'] += $data['total_earnings'];
            $departmentBreakdown[$department]['total_gross_salary'] += $data['gross_salary'];
        }
        
        return $departmentBreakdown;
    }
    
    /**
     * Calculate department deductions breakdown
     */
    private function calculateDepartmentDeductionsBreakdown($deductionsData): array
    {
        $departmentBreakdown = [];
        
        foreach ($deductionsData as $data) {
            $department = $data['department'] ?? 'Unassigned';
            
            if (!isset($departmentBreakdown[$department])) {
                $departmentBreakdown[$department] = [
                    'employee_count' => 0,
                    'total_deductions' => 0,
                    'total_paye_tax' => 0,
                ];
            }
            
            $departmentBreakdown[$department]['employee_count']++;
            $departmentBreakdown[$department]['total_deductions'] += $data['total_deductions'];
            $departmentBreakdown[$department]['total_paye_tax'] += $data['paye_tax'];
        }
        
        return $departmentBreakdown;
    }
    
    /**
     * Helper method to build filter info - NO DEFAULTS
     */
    private function buildFilterInfo(array $filters): array
    {
        $filterInfo = [];
        
        if (!empty($filters['business_id'])) {
            $business = Business::find($filters['business_id']);
            $filterInfo['business'] = $business ? $business->name : null;
            $filterInfo['business_id'] = $filters['business_id'];
        }
        
        if (!empty($filters['country'])) {
            if (is_numeric($filters['country'])) {
                $country = Country::find($filters['country']);
            } else {
                $country = Country::where('code', $filters['country'])->first();
            }
            
            if ($country) {
                $filterInfo['country'] = $country->name;
                $filterInfo['country_code'] = $country->code;
                $filterInfo['currency'] = $country->currency;
                $filterInfo['currency_symbol'] = $country->currency_symbol;
            }
            // No else clause — don't set defaults
        }
        
        if (!empty($filters['department'])) {
            $filterInfo['department'] = $filters['department'];
        }
        
        if (!empty($filters['start_date'])) {
            $filterInfo['start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $filterInfo['end_date'] = $filters['end_date'];
        }
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $filterInfo['status'] = $filters['status'];
        }
        
        return $filterInfo;
    }
  
    /**
     * Calculate deduction breakdown
     */
    private function calculateDeductionBreakdown($payslips): array
    {
        $deductionBreakdown = [];
        
        foreach ($payslips as $payslip) {
            $deductions = $payslip['deductions'] ?? [];
            
            foreach ($deductions as $name => $deductionData) {
                if (!isset($deductionBreakdown[$name])) {
                    $deductionBreakdown[$name] = [
                        'name' => $name,
                        'type' => is_array($deductionData) ? ($deductionData['type'] ?? 'unknown') : 'unknown',
                        'description' => is_array($deductionData) ? ($deductionData['description'] ?? $name) : $name,
                        'total_amount' => 0,
                        'employee_count' => 0,
                    ];
                }
                
                $amount = is_array($deductionData) ? ($deductionData['amount'] ?? 0) : $deductionData;
                $deductionBreakdown[$name]['total_amount'] += $amount;
                $deductionBreakdown[$name]['employee_count']++;
            }
        }
        
        return array_values($deductionBreakdown);
    }

    /**
     * Calculate earning breakdown
     */
    private function calculateEarningBreakdown($payslips): array
    {
        $earningBreakdown = [];
        
        foreach ($payslips as $payslip) {
            $earnings = $payslip['earnings'] ?? [];
            
            foreach ($earnings as $name => $earningData) {
                if (!isset($earningBreakdown[$name])) {
                    $earningBreakdown[$name] = [
                        'name' => $name,
                        'type' => is_array($earningData) ? ($earningData['type'] ?? 'regular') : 'regular',
                        'description' => is_array($earningData) ? ($earningData['description'] ?? $name) : $name,
                        'total_amount' => 0,
                        'employee_count' => 0,
                    ];
                }
                
                $amount = is_array($earningData) ? ($earningData['amount'] ?? 0) : $earningData;
                $earningBreakdown[$name]['total_amount'] += $amount;
                $earningBreakdown[$name]['employee_count']++;
            }
        }
        
        return array_values($earningBreakdown);
    }

    /**
     * Calculate deductions by type
     */
    private function calculateDeductionsByType($payslips): array
    {
        $deductionsByType = [];
        
        foreach ($payslips as $payslip) {
            $deductions = $payslip['deductions'] ?? [];
            
            foreach ($deductions as $name => $deductionData) {
                $type = is_array($deductionData) ? ($deductionData['type'] ?? 'other') : 'other';
                $amount = is_array($deductionData) ? ($deductionData['amount'] ?? 0) : $deductionData;
                
                if (!isset($deductionsByType[$type])) {
                    $deductionsByType[$type] = 0;
                }
                
                $deductionsByType[$type] += $amount;
            }
        }
        
        return $deductionsByType;
    }

    /**
     * Calculate earnings by type
     */
    private function calculateEarningsByType($payslips): array
    {
        $earningsByType = [];
        
        foreach ($payslips as $payslip) {
            $earnings = $payslip['earnings'] ?? [];
            
            foreach ($earnings as $name => $earningData) {
                $type = is_array($earningData) ? ($earningData['type'] ?? 'regular') : 'regular';
                $amount = is_array($earningData) ? ($earningData['amount'] ?? 0) : $earningData;
                
                if (!isset($earningsByType[$type])) {
                    $earningsByType[$type] = 0;
                }
                
                $earningsByType[$type] += $amount;
            }
        }
        
        return $earningsByType;
    }

    /**
     * Map deduction field to type
     */
    private function mapDeductionType($field): string
    {
        $typeMapping = [
            'paye' => 'tax',
            'napsa' => 'statutory',
            'nhima' => 'statutory',
            'pension' => 'pension',
            'social_security' => 'statutory',
            'provident_fund' => 'pension',
            'health_insurance' => 'health',
            'union_dues' => 'voluntary',
            'loan_deduction' => 'loan',
            'advance_deduction' => 'advance',
        ];
        
        return $typeMapping[$field] ?? 'other';
    }

    /**
     * Generate attendance report with business and country filters
     */
    public function generateAttendanceReport(array $filters = [])
    {
        Log::info('REPORT_SERVICE: Generating attendance report', [
            'filters' => $filters
        ]);
        $query = Attendance::with(['employee.user', 'employee.business', 'employee.country']);
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (isset($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }
        // Apply business filter
        if (isset($filters['business_id']) && !empty($filters['business_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('business_id', $filters['business_id']);
            });
            
            Log::info('REPORT_SERVICE: Applied business filter', [
                'business_id' => $filters['business_id']
            ]);
        }
        // Apply country filter
        if (isset($filters['country']) && !empty($filters['country'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('country_id', $filters['country']);
            });
            
            Log::info('REPORT_SERVICE: Applied country filter', [
                'country_id' => $filters['country']
            ]);
        }
        $attendances = $query->orderBy('date', 'desc')->get();
        Log::info('REPORT_SERVICE: Attendances retrieved', [
            'count' => $attendances->count()
        ]);
        $summary = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'attendance_rate' => $attendances->count() > 0 ?
                ($attendances->where('status', 'present')->count() / $attendances->count()) * 100 : 0,
        ];
        // Format attendance data for export
        $attendanceData = $attendances->map(function ($attendance) {
            return [
                'employee_name' => $attendance->employee->user
                    ? ($attendance->employee->user->first_name . ' ' . $attendance->employee->user->last_name)
                    : 'N/A',
                'department' => $attendance->employee->department ?? 'N/A',
                'business' => $attendance->employee->business ? $attendance->employee->business->name : 'No Business',
                'country' => $attendance->employee->country ? $attendance->employee->country->name : 'N/A',
                'date' => Carbon::parse($attendance->date)->format('M d, Y'),
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i A') : 'N/A',
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i A') : 'N/A',
                'total_hours' => $attendance->total_hours ?? 0,
                'status' => $attendance->status ?? 'N/A',
            ];
        })->toArray();
        // Add filter info
        $filterInfo = $this->buildFilterInfo($filters);
        return [
            'data' => $attendanceData,
            'summary' => array_merge($summary, ['filters' => $filterInfo]),
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate leave report with business and country filters
     */
    public function generateLeaveReport(array $filters = [])
    {
        Log::info('REPORT_SERVICE: Generating leave report', [
            'filters' => $filters
        ]);
        $query = Leave::with(['employee.user', 'employee.business', 'employee.country']);
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }
        // Apply business filter
        if (isset($filters['business_id']) && !empty($filters['business_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('business_id', $filters['business_id']);
            });
            
            Log::info('REPORT_SERVICE: Applied business filter', [
                'business_id' => $filters['business_id']
            ]);
        }
        // Apply country filter
        if (isset($filters['country']) && !empty($filters['country'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('country_id', $filters['country']);
            });
            
            Log::info('REPORT_SERVICE: Applied country filter', [
                'country_id' => $filters['country']
            ]);
        }
        $leaves = $query->orderBy('created_at', 'desc')->get();
        Log::info('REPORT_SERVICE: Leaves retrieved', [
            'count' => $leaves->count()
        ]);
        $summary = [
            'total_leaves' => $leaves->count(),
            'approved_leaves' => $leaves->where('status', 'approved')->count(),
            'pending_leaves' => $leaves->where('status', 'pending')->count(),
            'rejected_leaves' => $leaves->where('status', 'rejected')->count(),
            'total_days' => $leaves->sum('total_days'),
            'approval_rate' => $leaves->whereIn('status', ['approved', 'rejected'])->count() > 0 ?
                ($leaves->where('status', 'approved')->count() / $leaves->whereIn('status', ['approved', 'rejected'])->count()) * 100 : 0,
        ];
        // Format leave data for export
        $leaveData = $leaves->map(function ($leave) {
            return [
                'employee_name' => $leave->employee->user
                    ? ($leave->employee->user->first_name . ' ' . $leave->employee->user->last_name)
                    : 'N/A',
                'department' => $leave->employee->department ?? 'N/A',
                'business' => $leave->employee->business ? $leave->employee->business->name : 'No Business',
                'country' => $leave->employee->country ? $leave->employee->country->name : 'N/A',
                'leave_type' => $leave->type ?? 'N/A',
                'start_date' => Carbon::parse($leave->start_date)->format('M d, Y'),
                'end_date' => Carbon::parse($leave->end_date)->format('M d, Y'),
                'total_days' => $leave->total_days ?? 0,
                'status' => $leave->status ?? 'N/A',
                'reason' => $leave->reason ?? 'N/A',
            ];
        })->toArray();
        // Add filter info
        $filterInfo = $this->buildFilterInfo($filters);
        return [
            'data' => $leaveData,
            'summary' => array_merge($summary, ['filters' => $filterInfo]),
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportToPdf(string $view, array $data, string $filename)
    {
        try {
            Log::info('REPORT_SERVICE: Generating PDF', [
                'view' => $view,
                'filename' => $filename,
                'data_keys' => array_keys($data)
            ]);
            // Prepare data for the view
            $reportData = $this->prepareDataForPdf($data);
            // Generate PDF
            $pdf = PDF::loadView($view, ['report' => $reportData])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'defaultFont' => 'sans-serif'
                ]);
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('REPORT_SERVICE: PDF Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare data structure for PDF views.
     *
     * Currency and country values are passed through as-is from the report data.
     * No defaults are injected — the Blade view is responsible for handling null values gracefully
     * (e.g. showing "Multi-currency" or omitting the symbol when currency is null).
     */
    private function prepareDataForPdf(array $data): array
    {
        Log::info('REPORT_SERVICE: Preparing data for PDF', [
            'data_structure' => array_keys($data),
            'has_summary' => isset($data['summary']),
            'has_data' => isset($data['data'])
        ]);

        $preparedData = [];

        // Scenario 1: Data comes directly from ReportController (wrapped in 'success' and 'data')
        if (isset($data['success']) && isset($data['data']) && is_array($data['data'])) {
            Log::info('REPORT_SERVICE: Detected Controller JSON response structure, unpacking data');
            $preparedData = $data['data'];
        }
        // Scenario 2: Data comes from ReportGeneratorService's own generateXReport methods (has 'summary' and 'data')
        elseif (isset($data['summary']) && isset($data['data'])) {
            Log::info('REPORT_SERVICE: Detected Service output structure');
            $preparedData = array_merge($data['summary'], ['payslip_details' => $data['data'] ?? []]);
            $preparedData['generated_at'] = $data['generated_at'] ?? now();
        }
        // Scenario 3: Data is already a flattened array
        else {
            Log::info('REPORT_SERVICE: Detected flattened data structure, using as is.');
            $preparedData = $data;
        }

        // Period dates — use values from the data; null if not provided (no invented defaults)
        $preparedData['period_start'] = $preparedData['start_date']
            ?? $preparedData['period_start']
            ?? null;

        $preparedData['period_end'] = $preparedData['end_date']
            ?? $preparedData['period_end']
            ?? null;

        // Tax-related key normalisation (no numeric defaults — keep null if absent)
        $preparedData['total_paye_tax'] = $preparedData['total_paye_tax']
            ?? $preparedData['total_paye']
            ?? null;

        $preparedData['total_tax_withheld'] = $preparedData['total_tax_withheld']
            ?? $preparedData['total_tax_amount']
            ?? null;

        // Earnings / deductions totals — normalise key names, preserve null if absent
        $preparedData['total_earnings'] = $preparedData['total_earnings']
            ?? $preparedData['total_gross_salary']
            ?? null;

        $preparedData['total_deductions'] = $preparedData['total_all_deductions']
            ?? $preparedData['total_deductions']
            ?? null;

        // Currency — pass through exactly what the report resolved; never inject a default
        // $preparedData['currency'] and $preparedData['currency_symbol'] are already set
        // (or null) from the summary; no action needed here.

        Log::info('REPORT_SERVICE: Final data structure for PDF view', [
            'keys' => array_keys($preparedData),
            'period_start' => $preparedData['period_start'],
            'period_end' => $preparedData['period_end'],
            'total_paye_tax' => $preparedData['total_paye_tax'],
            'total_tax_withheld' => $preparedData['total_tax_withheld'],
            'total_earnings' => $preparedData['total_earnings'],
            'total_deductions' => $preparedData['total_deductions'],
            'currency' => $preparedData['currency'] ?? null,
            'currency_symbol' => $preparedData['currency_symbol'] ?? null,
            'is_multi_currency' => $preparedData['is_multi_currency'] ?? null,
        ]);

        return $preparedData;
    }

    /**
     * Updated CSV Export to handle dynamic columns for payroll (with earnings and deductions)
     */
    public function exportToCsv(array $data, string $filename, string $reportType = 'payroll')
    {
        $callback = function() use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            // Check if this is the dynamic payroll report structure
            if ($reportType === 'payroll' && isset($data['summary']['earning_headers']) && isset($data['summary']['deduction_headers']) && isset($data['data'])) {
                $earningHeaders = $data['summary']['earning_headers'];
                $deductionHeaders = $data['summary']['deduction_headers'];
                
                // Build CSV Headers
                $csvHeaders = ['Employee', 'Employee ID', 'Business', 'Country', 'Department', 'Pay Period', 'Gross Salary'];
                
                // Add earning columns
                foreach ($earningHeaders as $h) {
                    $csvHeaders[] = $h . ' (Earnings)';
                }
                
                $csvHeaders[] = 'Total Earnings';
                
                // Add deduction columns
                foreach ($deductionHeaders as $h) {
                    $csvHeaders[] = $h . ' (Deductions)';
                }
                
                $csvHeaders[] = 'Total Deductions';
                $csvHeaders[] = 'Net Salary';
                $csvHeaders[] = 'Status';
                
                fputcsv($file, $csvHeaders);

                // Build Rows
                foreach ($data['data'] as $row) {
                    $csvRow = [
                        $row['employee_name'],
                        $row['employee_id'],
                        $row['business'],
                        $row['country'],
                        $row['department'],
                        $row['pay_period'],
                        $row['gross_salary']
                    ];

                    // Map dynamic earnings to columns (fill 0 if empty)
                    foreach ($earningHeaders as $h) {
                        $csvRow[] = $row['earnings_breakdown'][$h] ?? 0;
                    }

                    $csvRow[] = $row['total_earnings'] ?? $row['gross_salary'];

                    // Map dynamic deductions to columns (fill 0 if empty)
                    foreach ($deductionHeaders as $h) {
                        $csvRow[] = $row['deductions_breakdown'][$h] ?? 0;
                    }

                    $csvRow[] = $row['total_deductions'];
                    $csvRow[] = $row['net_salary'];
                    $csvRow[] = $row['status'];

                    fputcsv($file, $csvRow);
                }
            } 
            // Earnings-only report
            elseif ($reportType === 'earnings' && isset($data['summary']['earning_headers']) && isset($data['data'])) {
                $earningHeaders = $data['summary']['earning_headers'];
                
                // Build CSV Headers
                $csvHeaders = ['Employee', 'Employee ID', 'Business', 'Country', 'Department', 'Pay Period'];
                
                // Add earning columns
                foreach ($earningHeaders as $h) {
                    $csvHeaders[] = $h;
                }
                
                $csvHeaders[] = 'Total Earnings';
                $csvHeaders[] = 'Gross Salary';
                $csvHeaders[] = 'Status';
                
                fputcsv($file, $csvHeaders);

                // Build Rows
                foreach ($data['data'] as $row) {
                    $csvRow = [
                        $row['employee_name'],
                        $row['employee_id'],
                        $row['business'],
                        $row['country'],
                        $row['department'],
                        $row['pay_period']
                    ];

                    // Map dynamic earnings to columns (fill 0 if empty)
                    foreach ($earningHeaders as $h) {
                        $csvRow[] = $row['earnings_breakdown'][$h] ?? 0;
                    }

                    $csvRow[] = $row['total_earnings'];
                    $csvRow[] = $row['gross_salary'];
                    $csvRow[] = $row['status'];

                    fputcsv($file, $csvRow);
                }
            }
            // Deductions-only report
            elseif ($reportType === 'deductions' && isset($data['summary']['deduction_headers']) && isset($data['data'])) {
                $deductionHeaders = $data['summary']['deduction_headers'];
                
                // Build CSV Headers
                $csvHeaders = ['Employee', 'Employee ID', 'Business', 'Country', 'Department', 'Pay Period'];
                
                // Add deduction columns
                foreach ($deductionHeaders as $h) {
                    $csvHeaders[] = $h;
                }
                
                $csvHeaders[] = 'Total Deductions';
                $csvHeaders[] = 'PAYE Tax';
                $csvHeaders[] = 'Net Salary';
                $csvHeaders[] = 'Status';
                
                fputcsv($file, $csvHeaders);

                // Build Rows
                foreach ($data['data'] as $row) {
                    $csvRow = [
                        $row['employee_name'],
                        $row['employee_id'],
                        $row['business'],
                        $row['country'],
                        $row['department'],
                        $row['pay_period']
                    ];

                    // Map dynamic deductions to columns (fill 0 if empty)
                    foreach ($deductionHeaders as $h) {
                        $csvRow[] = $row['deductions_breakdown'][$h] ?? 0;
                    }

                    $csvRow[] = $row['total_deductions'];
                    $csvRow[] = $row['paye_tax'] ?? 0;
                    $csvRow[] = $row['net_salary'];
                    $csvRow[] = $row['status'];

                    fputcsv($file, $csvRow);
                }
            }
            // Fallback for non-payroll reports (Attendance/Leave)
            elseif (!empty($data) && isset($data[0])) {
                $headers = array_keys($data[0]);
                fputcsv($file, array_map(fn($h) => str_replace('_', ' ', ucwords($h)), $headers));
                foreach ($data as $row) fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate productivity report
     */
    public function generateProductivityReport(array $filters = []): array
    {
        $query = Employee::with(['user', 'attendances', 'leaves', 'business', 'country']);
        if (isset($filters['employee_id'])) {
            $query->where('id', $filters['employee_id']);
        }
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        // Apply business filter
        if (isset($filters['business_id']) && !empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }
        // Apply country filter
        if (isset($filters['country']) && !empty($filters['country'])) {
            $query->where('country_id', $filters['country']);
        }
        $employees = $query->get();
        $productivityData = $employees->map(function ($employee) use ($filters) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name ?? 'N/A',
                'email' => $employee->user->email ?? 'N/A',
                'department' => $employee->department ?? 'N/A',
                'business' => $employee->business ? $employee->business->name : 'No Business',
                'country' => $employee->country ? $employee->country->name : 'N/A',
                'total_working_days' => $this->getTotalWorkingDays($employee, $filters),
                'present_days' => $this->getPresentDays($employee, $filters),
                'late_days' => $this->getLateDays($employee, $filters),
                'absent_days' => $this->getAbsentDays($employee, $filters),
                'total_hours_worked' => $this->getTotalHoursWorked($employee, $filters),
                'approved_leave_days' => $this->getApprovedLeaveDays($employee, $filters),
                'attendance_rate' => $this->calculateAttendanceRate($employee, $filters),
                'productivity_score' => $this->calculateProductivityScore($employee, $filters),
            ];
        });
        $summary = [
            'total_employees' => $employees->count(),
            'average_attendance_rate' => round($productivityData->avg('attendance_rate'), 2),
            'average_productivity_score' => round($productivityData->avg('productivity_score'), 2),
            'total_hours_worked' => round($productivityData->sum('total_hours_worked'), 2),
            'average_hours_per_employee' => $employees->count() > 0 ?
                round($productivityData->sum('total_hours_worked') / $employees->count(), 2) : 0,
        ];
        // Add filter info
        $filterInfo = $this->buildFilterInfo($filters);
        return [
            'data' => $productivityData->toArray(),
            'summary' => array_merge($summary, ['filters' => $filterInfo]),
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Calculate productivity score
     */
    private function calculateProductivityScore(Employee $employee, array $filters): float
    {
        $attendanceRate = $this->calculateAttendanceRate($employee, $filters);
        $lateDays = $this->getLateDays($employee, $filters);
        $totalDays = $this->getTotalWorkingDays($employee, $filters);
        
        // Base score from attendance rate (0-70 points)
        $attendanceScore = ($attendanceRate / 100) * 70;
        
        // Deduct points for late days (up to 20 points)
        $latenessPenalty = $totalDays > 0 ? ($lateDays / $totalDays) * 20 : 0;
        
        // Consistency bonus (up to 30 points) - full bonus if attendance rate is above 95%
        $consistencyBonus = $attendanceRate >= 95 ? 30 : ($attendanceRate / 95) * 30;
        
        $score = $attendanceScore - $latenessPenalty + $consistencyBonus;
        
        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    private function getTotalWorkingDays(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances();
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        return $attendanceQuery->count();
    }

    private function getPresentDays(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances()->where('status', 'present');
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        return $attendanceQuery->count();
    }

    private function getLateDays(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances()->where('status', 'late');
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        return $attendanceQuery->count();
    }

    private function getAbsentDays(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances()->where('status', 'absent');
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        return $attendanceQuery->count();
    }

    private function getTotalHoursWorked(Employee $employee, array $filters): float
    {
        $attendanceQuery = $employee->attendances();
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        return $attendanceQuery->sum('total_hours');
    }

    private function getApprovedLeaveDays(Employee $employee, array $filters): int
    {
        $leaveQuery = $employee->leaves()->where('status', 'approved');
        
        if (isset($filters['start_date'])) {
            $leaveQuery->where('start_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $leaveQuery->where('end_date', '<=', $filters['end_date']);
        }
        return $leaveQuery->sum('total_days');
    }

    private function calculateAttendanceRate(Employee $employee, array $filters): float
    {
        $totalDays = $this->getTotalWorkingDays($employee, $filters);
        $presentDays = $this->getPresentDays($employee, $filters);
        return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
    }
}
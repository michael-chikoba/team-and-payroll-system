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

    // =========================================================================
    // PUBLIC — PREVIEW
    // =========================================================================

    /**
     * Generate a payslip preview (no DB save).
     */
    public function generatePayslipPreview(Employee $employee, Payroll $payroll): array
    {
        $employee->load(['country', 'business']);

        $taxConfig = $this->getTaxConfigForEmployee($employee);

        if (!$taxConfig) {
            throw new \Exception(
                "No active tax configuration found for employee #{$employee->id} " .
                "(country: {$employee->getCountryCode()}, business: {$employee->business_id})"
            );
        }

        $this->validateTaxConfig($taxConfig, $employee);
        $this->logTaxConfigUsage($employee, $taxConfig, 'preview');

        return $this->calculatePayslipData($employee, $payroll, $taxConfig, 'pending');
    }

    // =========================================================================
    // PUBLIC — CREATE
    // =========================================================================

    /**
     * Create and save a payslip (no adjustments).
     */
    public function createPayslip(Employee $employee, Payroll $payroll, string $status = 'pending'): ?Payslip
    {
        return $this->createPayslipWithAdjustments($employee, $payroll, $status, []);
    }

    /**
     * Create and save a payslip with optional bonuses / deductions.
     *
     * NOTE: This method always creates a FRESH payslip row.
     * The caller (PayrollController) is responsible for deleting any
     * existing payslips before calling this method if a clean
     * recalculation is required (i.e. after a revert-to-pending).
     */
    public function createPayslipWithAdjustments(
        Employee $employee,
        Payroll  $payroll,
        string   $status      = 'pending',
        array    $adjustments = []
    ): ?Payslip {
        try {
            $employee->load(['country', 'business']);

            // 1. Resolve tax config ─────────────────────────────────────────
            $taxConfig = $this->getTaxConfigForEmployee($employee);

            if (!$taxConfig) {
                Log::error('No tax configuration found', [
                    'employee_id'  => $employee->id,
                    'business_id'  => $employee->business_id,
                    'country_code' => $employee->getCountryCode(),
                ]);
                throw new \Exception(
                    "No active tax configuration found for employee #{$employee->id} " .
                    "(country: {$employee->getCountryCode()}, business: {$employee->business_id})"
                );
            }

            // 2. Validate ───────────────────────────────────────────────────
            $this->validateTaxConfig($taxConfig, $employee);
            $this->logTaxConfigUsage($employee, $taxConfig, 'creation');

            // 3. Resolve adjustments before core calculation ────────────────
            $bonuses = (float) ($adjustments['overtime_bonus'] ?? 0)
                     + (float) ($adjustments['other_bonuses']   ?? 0);

            $additionalDeductions = (float) ($adjustments['loan_deductions']    ?? 0)
                                  + (float) ($adjustments['advance_deductions'] ?? 0);

            // 4. Core calculation (bonuses included in gross) ───────────────
            $calculationData = $this->calculatePayslipData(
                $employee, $payroll, $taxConfig, $status, $bonuses
            );

            // 5. Apply extra deductions post-calculation ────────────────────
            if ($additionalDeductions > 0) {
                $calculationData['deductions']['other_deductions'] += $additionalDeductions;
                $calculationData['deductions']['total_deductions'] += $additionalDeductions;
                $calculationData['net_pay'] = $calculationData['gross_salary']
                                            - $calculationData['deductions']['total_deductions'];

                $calculationData['breakdown']['adjustments']         = $adjustments;
                $calculationData['breakdown']['adjustments_applied'] = [
                    'total_bonuses'              => $bonuses,
                    'total_additional_deductions' => $additionalDeductions,
                    'net_effect'                  => $bonuses - $additionalDeductions,
                ];
            }

            // 6. Map dynamic statutory breakdown → legacy DB columns ────────
            $statutory = collect($calculationData['deductions']['statutory_breakdown']);

            // FIXED: Use safeSum with float casting
            $napsaAmount = $statutory
                ->where('type', 'levy')
                ->filter(fn ($i) => stripos($i['name'] ?? '', 'NAPSA') !== false)
                ->sum(function($item) {
                    return (float) ($item['amount'] ?? 0);
                });

            $nhimaAmount = $statutory
                ->where('type', 'health')
                ->filter(fn ($i) => stripos($i['name'] ?? '', 'NHIMA') !== false
                                 || stripos($i['name'] ?? '', 'NHIF')  !== false)
                ->sum(function($item) {
                    return (float) ($item['amount'] ?? 0);
                });

            $pensionAmount = $statutory
                ->where('type', 'pension')
                ->filter(fn ($i) => stripos($i['name'] ?? '', 'NAPSA') === false)
                ->sum(function($item) {
                    return (float) ($item['amount'] ?? 0);
                });

            Log::debug('Creating payslip record', [
                'employee_id'    => $employee->id,
                'payroll_id'     => $payroll->id,
                'country_code'   => $employee->getCountryCode(),
                'tax_config_id'  => $taxConfig->id,
                'basic_salary'   => $calculationData['basic_salary'],
                'bonuses'        => $calculationData['bonuses'],
                'gross_salary'   => $calculationData['gross_salary'],
                'taxable_income' => $calculationData['deductions']['taxable_income'] ?? 'n/a',
                'paye'           => $calculationData['deductions']['paye'],
                'napsa'          => $napsaAmount,
                'nhima'          => $nhimaAmount,
                'pension'        => $pensionAmount,
                'net_pay'        => $calculationData['net_pay'],
            ]);

            return Payslip::create([
                'employee_id'         => $employee->id,
                'payroll_id'          => $payroll->id,
                'pay_period_start'    => $payroll->start_date,
                'pay_period_end'      => $payroll->end_date,
                'payment_date'        => $payroll->end_date,
                'basic_salary'        => $calculationData['basic_salary'],
                'house_allowance'     => $calculationData['allowances']['housing'],
                'transport_allowance' => $calculationData['allowances']['transport'],
                'other_allowances'    => $calculationData['allowances']['lunch'],
                'overtime_hours'      => $calculationData['overtime_hours'],
                'overtime_rate'       => $calculationData['overtime_rate'],
                'overtime_pay'        => $calculationData['overtime_pay'],
                'bonuses'             => $calculationData['bonuses'],
                'gross_salary'        => $calculationData['gross_salary'],
                'napsa'               => $napsaAmount,
                'nhima'               => $nhimaAmount,
                'pension'             => $pensionAmount,
                'paye'                => $calculationData['deductions']['paye'],
                'other_deductions'    => $calculationData['deductions']['other_deductions'],
                'total_deductions'    => $calculationData['deductions']['total_deductions'],
                'net_pay'             => $calculationData['net_pay'],
                'status'              => $status,
                'breakdown'           => $calculationData['breakdown'],
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create payslip for employee {$employee->id}: " . $e->getMessage(), [
                'employee_id'   => $employee->id,
                'employee_name' => ($employee->user->first_name ?? 'Unknown'),
                'business_id'   => $employee->business_id,
                'country_code'  => $employee->getCountryCode(),
                'trace'         => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // PUBLIC — REVERT HELPER
    // =========================================================================

    /**
     * Delete ALL payslip rows for a given employee in a given payroll period.
     *
     * Returns the number of rows deleted.  If the payroll has no remaining
     * payslips after deletion the caller should reset the payroll to 'draft'.
     *
     * This is the canonical place to perform the deletion so that both the
     * controller AND any future artisan commands / jobs use the same logic.
     */
    public function deletePayslipsForEmployee(Employee $employee, Payroll $payroll): int
    {
        $deleted = Payslip::where('payroll_id', $payroll->id)
            ->where('employee_id', $employee->id)
            ->delete();

        Log::info('PayrollCalculationService: Payslips deleted for revert', [
            'employee_id' => $employee->id,
            'payroll_id'  => $payroll->id,
            'rows_deleted'=> $deleted,
        ]);

        return (int) $deleted;
    }

    // =========================================================================
    // PAYROLL TOTALS - FIXED
    // =========================================================================

    /**
     * Recalculate and persist summary totals on the Payroll record.
     * Should be called after any create / delete of payslip rows.
     */
    public function updatePayrollTotals(Payroll $payroll): void
{
    try {
        $payslips = $payroll->payslips()->get();

        $totalGross = $payslips->sum(function ($payslip) {
            $value = $payslip->getForcedDecrypted('gross_salary');
            return is_numeric($value) ? (float) $value : 0.0;
        });

        $totalNet = $payslips->sum(function ($payslip) {
            $value = $payslip->getForcedDecrypted('net_pay');
            return is_numeric($value) ? (float) $value : 0.0;
        });

        $employeeCount = $payslips->count();

        Log::debug('PayrollCalculationService: Updating payroll totals', [
            'payroll_id'             => $payroll->id,
            'total_gross_calculated' => $totalGross,
            'total_net_calculated'   => $totalNet,
            'employee_count'         => $employeeCount,
            'payslips_count'         => $payslips->count(),
        ]);

        $payroll->update([
            'total_gross'    => $totalGross,
            'total_net'      => $totalNet,
            'employee_count' => $employeeCount,
        ]);

        Log::info('PayrollCalculationService: Payroll totals updated', [
            'payroll_id'     => $payroll->id,
            'total_gross'    => $totalGross,
            'total_net'      => $totalNet,
            'employee_count' => $employeeCount,
        ]);

    } catch (\Exception $e) {
        Log::error('PayrollCalculationService: Failed to update payroll totals', [
            'payroll_id' => $payroll->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
    // =========================================================================
    // TAX CONFIG RESOLUTION
    // =========================================================================

    /**
     * Resolve the correct TaxConfiguration for an employee.
     *
     * Priority:
     *   1. Business-specific  (business_id + country_code both match)
     *   2. Country-specific   (country_code matches, business_id null)
     *   3. Global fallback    (both null)
     */
    private function getTaxConfigForEmployee(Employee $employee): ?TaxConfiguration
    {
        $businessId  = $employee->business_id;
        $countryCode = $employee->getCountryCode();

        Log::info('PayrollCalculationService: Resolving tax configuration for employee', [
            'employee_id'           => $employee->id,
            'employee_name'         => ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''),
            'business_id'           => $businessId,
            'employee_country_id'   => $employee->country_id,
            'employee_country_code' => $countryCode,
            'business_country_code' => $employee->business->country_code ?? null,
        ]);

        if (!$countryCode) {
            Log::warning('PayrollCalculationService: Employee has no country code', [
                'employee_id' => $employee->id,
                'business_id' => $businessId,
            ]);
        }

        $config = TaxConfiguration::getForBusinessAndCountry($businessId, $countryCode);

        if (!$config) {
            Log::error('PayrollCalculationService: No active tax configuration found', [
                'employee_id'  => $employee->id,
                'employee_name'=> ($employee->user->first_name ?? 'Unknown'),
                'business_id'  => $businessId,
                'business_name'=> $employee->business->name ?? 'Unknown',
                'country_code' => $countryCode ?? 'Not set',
                'hint'         => 'Check tax_configurations table: ensure is_active=1 for this country/business combination',
            ]);
        }

        return $config;
    }

    /**
     * Validate that the resolved TaxConfiguration has minimum required data.
     */
    private function validateTaxConfig(TaxConfiguration $taxConfig, Employee $employee): void
    {
        $configData  = $taxConfig->config_data;
        $countryCode = $employee->getCountryCode();
        $context     = [
            'tax_config_id'   => $taxConfig->id,
            'employee_id'     => $employee->id,
            'country_code'    => $countryCode,
            'tax_config_type' => $this->getTaxConfigType($taxConfig),
        ];

        if (empty($configData['taxBands'])) {
            Log::error('PayrollCalculationService: taxBands missing — PAYE will be zero', $context);
            throw new \Exception(
                "Tax configuration #{$taxConfig->id} for country '{$countryCode}' has no tax bands defined. " .
                "PAYE cannot be calculated."
            );
        }

        foreach ($configData['taxBands'] as $index => $band) {
            if (!isset($band['rate']) || !isset($band['lowerLimit'])) {
                Log::error("PayrollCalculationService: Tax band #{$index} malformed", array_merge($context, ['band' => $band]));
                throw new \Exception(
                    "Tax configuration #{$taxConfig->id}: tax band #{$index} missing 'rate' or 'lowerLimit'."
                );
            }
        }

        if (empty($configData['statutory_deductions'])) {
            Log::warning('PayrollCalculationService: No statutory_deductions in config', $context);
        }

        if (empty($configData['currency'])) {
            Log::warning('PayrollCalculationService: No currency in config — defaulting to USD', $context);
        }

        Log::info('PayrollCalculationService: Tax configuration validated', array_merge($context, [
            'tax_bands_count'            => count($configData['taxBands']),
            'statutory_deductions_count' => count($configData['statutory_deductions'] ?? []),
            'currency'                   => $configData['currency'] ?? 'USD',
            'calculation_method'         => $configData['taxCalculationMethod'] ?? 'non_cumulative',
        ]));
    }

    // =========================================================================
    // CORE CALCULATION
    // =========================================================================

    /**
     * Calculate complete payslip data.
     *
     * Flow:
     *   basic_salary
     *   + allowances (housing % from config, transport/lunch from employee)
     *   + overtime
     *   + bonuses                       ← included before deductions
     *   = gross_salary
     *
     *   gross_salary - pension deductions → taxable_income → PAYE
     *
     *   total_deductions = PAYE + all employee statutory + other
     *   net_pay = gross_salary - total_deductions
     */
    private function calculatePayslipData(
        Employee          $employee,
        Payroll           $payroll,
        TaxConfiguration  $taxConfig,
        string            $status,
        float             $bonuses = 0.0
    ): array {
        try {
            // 1. Basic salary - USE THE ACCESSOR METHOD TO GET DECRYPTED VALUE
            $basicSalary = $employee->base_salary_value ?? (float) $employee->base_salary;

            if ($basicSalary <= 0) {
                throw new \InvalidArgumentException(
                    "Employee #{$employee->id} has invalid base_salary ({$basicSalary}). Must be > 0."
                );
            }

            // 2. Allowances ─────────────────────────────────────────────────
            $allowances = $taxConfig->calculateAllowances($employee);

            Log::debug('PayrollCalculationService: Allowances calculated', [
                'employee_id'     => $employee->id,
                'basic_salary'    => $basicSalary,
                'housing'         => $allowances['housing'],
                'transport'       => $allowances['transport'],
                'lunch'           => $allowances['lunch'],
                'total'           => $allowances['total'],
                'housing_rate'    => $taxConfig->config_data['housingAllowanceRate'] ?? 25.0,
            ]);

            // 3. Overtime ───────────────────────────────────────────────────
            $overtimeData = $this->calculateOvertimeData($employee, $payroll);

            // 4. Gross salary (bonuses included) ────────────────────────────
            $grossSalary = $basicSalary
                         + $allowances['total']
                         + $overtimeData['pay']
                         + $bonuses;

            // 5. Deductions ─────────────────────────────────────────────────
            $deductions = $this->calculateDeductions($basicSalary, $grossSalary, $taxConfig, $employee);

            // 6. Net pay ────────────────────────────────────────────────────
            $netPay = $grossSalary - $deductions['total_deductions'];

            // 7. Full breakdown for JSON column ─────────────────────────────
            $breakdown = [
                'calculation_method'     => 'Dynamic Tax Configuration',
                'tax_config_id'          => $taxConfig->id,
                'tax_config_type'        => $this->getTaxConfigType($taxConfig),
                'tax_config_country'     => $taxConfig->country_code ?? 'global',
                'tax_config_business_id' => $taxConfig->business_id  ?? 'all',
                'employee_country_code'  => $employee->getCountryCode(),
                'currency'               => $taxConfig->getCurrency(),
                'encryption_notice'      => 'Employee salary fields are encrypted in database',
                'earnings_breakdown'     => [
                    'basic_salary' => $basicSalary,
                    'allowances'   => $allowances,
                    'overtime'     => $overtimeData,
                    'bonuses'      => $bonuses,
                    'gross_total'  => $grossSalary,
                ],
                'deductions_breakdown' => $deductions,
                'net_calculation'      => [
                    'gross_salary'     => $grossSalary,
                    'minus_deductions' => $deductions['total_deductions'],
                    'equals_net_pay'   => $netPay,
                ],
                'statutory_details' => $deductions['statutory_breakdown'] ?? [],
                'tax_bands_applied' => $taxConfig->config_data['taxBands'] ?? [],
            ];

            return [
                // Flat DB fields
                'basic_salary'   => $basicSalary,
                'allowances'     => $allowances,
                'overtime_hours' => $overtimeData['hours'],
                'overtime_rate'  => $overtimeData['rate'],
                'overtime_pay'   => $overtimeData['pay'],
                'bonuses'        => $bonuses,
                'gross_salary'   => $grossSalary,
                'deductions'     => $deductions,
                'net_pay'        => $netPay,
                'breakdown'      => $breakdown,
                'status'         => $status,

                // Structured arrays for API response
                'earnings' => [
                    'basic_salary'        => $basicSalary,
                    'house_allowance'     => $allowances['housing'],
                    'transport_allowance' => $allowances['transport'],
                    'lunch_allowance'     => $allowances['lunch'],
                    'overtime_pay'        => $overtimeData['pay'],
                    'bonuses'             => $bonuses,
                    'gross_salary'        => $grossSalary,
                ],
                'summary' => [
                    'gross_pay'        => $grossSalary,
                    'total_deductions' => $deductions['total_deductions'],
                    'net_pay'          => $netPay,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('PayrollCalculationService: Failed to calculate payslip data', [
                'employee_id' => $employee->id,
                'payroll_id'  => $payroll->id ?? null,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // DEDUCTIONS
    // =========================================================================

    private function calculateDeductions(
        float            $basicSalary,
        float            $grossSalary,
        TaxConfiguration $taxConfig,
        Employee         $employee
    ): array {
        // a) Statutory deductions from config
        $statutory = $taxConfig->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);

        // b) Taxable income (gross minus pension-type deductions)
        $taxableIncome         = $grossSalary;
        $pensionDeductionTotal = 0.0;

        foreach ($statutory['breakdown'] as $item) {
            if (($item['type'] ?? '') === 'pension') {
                $amount = (float) ($item['amount'] ?? 0);
                $taxableIncome         -= $amount;
                $pensionDeductionTotal += $amount;
            }
        }
        $taxableIncome = max(0.0, $taxableIncome);

        // c) PAYE from tax bands
        $paye = $taxConfig->calculatePAYE($taxableIncome);

        // d) Other deductions (loans etc. are handled by the controller)
        $otherDeductions = 0.0;

        // e) Total
        $totalDeductions = $paye + (float) ($statutory['total_employee'] ?? 0) + $otherDeductions;

        Log::debug('PayrollCalculationService: Deductions calculated', [
            'employee_id'        => $employee->id,
            'country_code'       => $employee->getCountryCode(),
            'tax_config_id'      => $taxConfig->id,
            'basic_salary'       => $basicSalary,
            'gross_salary'       => $grossSalary,
            'pension_deducted'   => $pensionDeductionTotal,
            'taxable_income'     => $taxableIncome,
            'paye'               => $paye,
            'statutory_employee' => $statutory['total_employee'] ?? 0,
            'statutory_employer' => $statutory['total_employer'] ?? 0,
            'statutory_count'    => count($statutory['breakdown'] ?? []),
            'total_deductions'   => $totalDeductions,
        ]);

        return [
            'paye'                => $paye,
            'taxable_income'      => $taxableIncome,
            'statutory_breakdown' => $statutory['breakdown'] ?? [],
            'statutory_total'     => (float) ($statutory['total_employee'] ?? 0),
            'employer_total'      => (float) ($statutory['total_employer'] ?? 0),
            'other_deductions'    => $otherDeductions,
            'total_deductions'    => $totalDeductions,
        ];
    }

    // =========================================================================
    // OVERTIME
    // =========================================================================

    /**
     * Hourly rate  = base_salary / 173.33  (40 h/wk × 52 wks / 12 months)
     * Overtime rate = hourly rate × 1.5
     */
    private function calculateOvertimeData(Employee $employee, Payroll $payroll): array
    {
        // Use the base_salary_value accessor to get the decrypted value
        $basicSalary = $employee->base_salary_value ?? (float) $employee->base_salary;
        
        $overtimeHours = $this->attendanceService->getOvertimeHours(
            $employee->id,
            $payroll->start_date,
            $payroll->end_date
        );

        $hourlyRate   = $basicSalary > 0 ? ($basicSalary / 173.33) : 0;
        $overtimeRate = $hourlyRate * 1.5;
        $overtimePay  = $overtimeHours * $overtimeRate;

        return [
            'hours' => round($overtimeHours, 2),
            'rate'  => round($overtimeRate,  2),
            'pay'   => round($overtimePay,   2),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getTaxConfigType(TaxConfiguration $taxConfig): string
    {
        if ($taxConfig->business_id && $taxConfig->country_code) return 'business-specific';
        if ($taxConfig->country_code) return 'country-specific';
        return 'global';
    }

    private function logTaxConfigUsage(Employee $employee, TaxConfiguration $taxConfig, string $operation): void
    {
        Log::info('PayrollCalculationService: Tax configuration applied', [
            'operation'                  => $operation,
            'employee_id'                => $employee->id,
            'employee_name'              => ($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''),
            'business_id'                => $employee->business_id,
            'business_name'              => $employee->business->name ?? 'Unknown',
            'employee_country_id'        => $employee->country_id,
            'employee_country_code'      => $employee->getCountryCode(),
            'tax_config_id'              => $taxConfig->id,
            'tax_config_type'            => $this->getTaxConfigType($taxConfig),
            'tax_config_country'         => $taxConfig->country_code ?? 'global',
            'tax_config_business_id'     => $taxConfig->business_id  ?? 'all',
            'currency'                   => $taxConfig->getCurrency(),
            'rounding_method'            => $taxConfig->config_data['roundingMethod'] ?? 'nearest',
            'tax_calculation_method'     => $taxConfig->config_data['taxCalculationMethod'] ?? 'non_cumulative',
            'statutory_deductions_count' => count($taxConfig->config_data['statutory_deductions'] ?? []),
            'tax_bands_count'            => count($taxConfig->config_data['taxBands'] ?? []),
            'encryption_status'          => 'Salary fields are encrypted in employee table, decrypted for calculation',
        ]);
    }
}
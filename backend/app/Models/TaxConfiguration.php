<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class TaxConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'country_code',
        'country_name',
        'state',
        'config_data',
        'is_active',
    ];

    protected $casts = [
        'config_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Currency symbol mapping
     */
    private static $currencySymbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'ZMW' => 'K',
        'ZAR' => 'R',
        'KES' => 'KSh',
        'UGX' => 'USh',
        'TZS' => 'TSh',
        'NGN' => '₦',
        'GHS' => '₵',
        'RWF' => 'FRw',
        'MWK' => 'MK',
        'BWP' => 'P',
        'INR' => '₹',
        'JPY' => '¥',
        'CNY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'Fr',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'PLN' => 'zł',
        'CZK' => 'Kč',
        'HUF' => 'Ft',
        'RON' => 'lei',
        'BGN' => 'лв',
        'HRK' => 'kn',
        'RSD' => 'din',
        'UAH' => '₴',
        'TRY' => '₺',
        'ILS' => '₪',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'QAR' => '﷼',
        'KWD' => 'د.ك',
        'OMR' => '﷼',
        'BHD' => 'د.ب',
        'EGP' => '£',
        'MAD' => 'د.م.',
        'TND' => 'د.ت',
        'DZD' => 'د.ج',
        'LYD' => 'ل.د',
        'ETB' => 'Br',
        'BRL' => 'R$',
        'MXN' => '$',
        'ARS' => '$',
        'CLP' => '$',
        'COP' => '$',
        'PEN' => 'S/',
        'VES' => 'Bs',
        'UYU' => '$U',
        'PYG' => '₲',
        'BOB' => 'Bs',
        'CRC' => '₡',
        'GTQ' => 'Q',
        'HNL' => 'L',
        'NIO' => 'C$',
        'PAB' => 'B/.',
        'DOP' => 'RD$',
    ];

    /**
     * Relationships
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode)->active();
    }

    public function scopeForBusiness($query, ?int $businessId)
    {
        return $query->where('business_id', $businessId)->active();
    }

    /**
     * Get the appropriate tax configuration
     * Priority: Business-specific > Country-specific > Global
     */
    public static function getForBusinessAndCountry(?int $businessId, ?string $countryCode): ?self
    {
        Log::debug('TaxConfiguration lookup', [
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);

        // Try business-specific config first
        if ($businessId && $countryCode) {
            $config = self::where('business_id', $businessId)
                ->where('country_code', $countryCode)
                ->active()
                ->first();

            if ($config) {
                Log::info('Tax config found: business-specific', [
                    'config_id' => $config->id,
                    'business_id' => $businessId,
                    'country_code' => $countryCode
                ]);
                return $config;
            }
        }

        // Fall back to country-specific config
        if ($countryCode) {
            $config = self::whereNull('business_id')
                ->where('country_code', $countryCode)
                ->active()
                ->first();

            if ($config) {
                Log::info('Tax config found: country-specific', [
                    'config_id' => $config->id,
                    'country_code' => $countryCode
                ]);
                return $config;
            }
        }

        // Last resort: global config
        $config = self::whereNull('business_id')
            ->whereNull('country_code')
            ->active()
            ->first();

        if ($config) {
            Log::info('Tax config found: global fallback', [
                'config_id' => $config->id
            ]);
            return $config;
        }

        Log::warning('No tax configuration found', [
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);

        return null;
    }

    /**
     * Get currency code from config
     */
    public function getCurrency(): string
    {
        return $this->config_data['currency'] ?? 'USD';
    }

    /**
     * Get currency symbol for the configured currency
     */
    public function getCurrencySymbol(): string
    {
        $currencyCode = $this->getCurrency();
        return self::$currencySymbols[$currencyCode] ?? $currencyCode;
    }

    /**
     * Get complete currency information
     */
    public function getCurrencyInfo(): array
    {
        $code = $this->getCurrency();
        $symbol = $this->getCurrencySymbol();

        return [
            'code'   => $code,
            'symbol' => $symbol,
            'name'   => $this->getCurrencyName($code),
        ];
    }

    /**
     * Get currency name from code
     */
    private function getCurrencyName(string $code): string
    {
        $names = [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'ZMW' => 'Zambian Kwacha',
            'ZAR' => 'South African Rand',
            'NAD' => 'Namibian Dollar',
            'BWP' => 'Botswana Pula',
            'MWK' => 'Malawian Kwacha',
            'LSL' => 'Lesotho Loti',
            'SZL' => 'Eswatini Lilangeni',
            'MZN' => 'Mozambican Metical',
            'ZWL' => 'Zimbabwean Dollar',
            'KES' => 'Kenyan Shilling',
            'UGX' => 'Ugandan Shilling',
            'TZS' => 'Tanzanian Shilling',
            'RWF' => 'Rwandan Franc',
            'BIF' => 'Burundian Franc',
            'ETB' => 'Ethiopian Birr',
            'SOS' => 'Somali Shilling',
            'NGN' => 'Nigerian Naira',
            'GHS' => 'Ghanaian Cedi',
            'XOF' => 'West African CFA Franc',
            'XAF' => 'Central African CFA Franc',
            'SLL' => 'Sierra Leonean Leone',
            'GMD' => 'Gambian Dalasi',
            'LRD' => 'Liberian Dollar',
            'CDF' => 'Congolese Franc',
            'STD' => 'São Tomé and Príncipe Dobra',
            'EGP' => 'Egyptian Pound',
            'MAD' => 'Moroccan Dirham',
            'DZD' => 'Algerian Dinar',
            'TND' => 'Tunisian Dinar',
            'LYD' => 'Libyan Dinar',
            'MUR' => 'Mauritian Rupee',
            'SCR' => 'Seychellois Rupee',
            'CVE' => 'Cape Verdean Escudo',
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Static method to get currency symbol by code
     */
    public static function getCurrencySymbolByCode(string $code): string
    {
        return self::$currencySymbols[$code] ?? $code;
    }

    /**
     * Get tax calculation method
     */
    public function getTaxCalculationMethod(): string
    {
        return $this->config_data['taxCalculationMethod'] ?? 'non_cumulative';
    }

    /**
     * Check if housing allowance should be included in calculations
     */
    public function includesHousingAllowance(): bool
    {
        return $this->config_data['includeHousingAllowance'] ?? false;
    }

    /**
     * Get housing allowance percentage from config
     */
    public function getHousingAllowanceRate(): float
    {
        return $this->config_data['housingAllowanceRate'] ?? 25.0;
    }

    /**
     * Calculate allowances based on employee data and config
     */
    public function calculateAllowances(Employee $employee): array
    {
        $basicSalary = (float) $employee->base_salary;

        // Housing allowance - only if enabled in config
        $housing = 0.0;
        if ($this->includesHousingAllowance()) {
            $rate    = $this->getHousingAllowanceRate() / 100;
            $housing = $basicSalary * $rate;
        }

        // Transport and lunch from employee record
        $transport = (float) ($employee->transport_allowance ?? 0.00);
        $lunch     = (float) ($employee->lunch_allowance    ?? 0.00);

        $total = $housing + $transport + $lunch;

        Log::debug('Allowances calculated', [
            'employee_id'    => $employee->id,
            'basic_salary'   => $basicSalary,
            'housing'        => $housing,
            'transport'      => $transport,
            'lunch'          => $lunch,
            'total'          => $total,
            'housing_enabled'=> $this->includesHousingAllowance(),
            'housing_rate'   => $this->getHousingAllowanceRate(),
        ]);

        return [
            'housing'   => $this->applyRounding($housing),
            'transport' => $this->applyRounding($transport),
            'lunch'     => $this->applyRounding($lunch),
            'total'     => $this->applyRounding($total),
        ];
    }

    /**
     * Calculate statutory deductions dynamically from config
     */
    public function calculateStatutoryDeductions(Employee $employee, float $basicSalary, float $grossSalary): array
    {
        $deductions = $this->config_data['statutory_deductions'] ?? [];

        $breakdown     = [];
        $totalEmployee = 0.0;
        $totalEmployer = 0.0;

        foreach ($deductions as $deduction) {
            $name = $deduction['name'];
            $type = $deduction['type'];

            // Skip pension for non-full-time employees
            if ($type === 'pension' && $employee->employment_type !== 'full_time') {
                Log::debug("Skipping {$name} for non-full-time employee", [
                    'employee_id'     => $employee->id,
                    'employment_type' => $employee->employment_type,
                ]);
                continue;
            }

            // Get base amount from config
            $base       = $deduction['base'] ?? 'basic';
            $baseAmount = ($base === 'gross') ? $grossSalary : $basicSalary;

            // Apply ceiling if specified
            $ceiling = isset($deduction['ceiling']) && $deduction['ceiling'] > 0
                ? (float) $deduction['ceiling']
                : null;

            if ($ceiling && $baseAmount > $ceiling) {
                $baseAmount = $ceiling;
            }

            // Get rates from config
            $employeeRate = (float) ($deduction['employee_rate'] ?? 0);
            $employerRate = (float) ($deduction['employer_rate'] ?? 0);

            // Calculate amounts
            $employeeAmount = $this->applyRounding(($baseAmount * $employeeRate) / 100);
            $employerAmount = $this->applyRounding(($baseAmount * $employerRate) / 100);

            $breakdown[] = [
                'name'                => $name,
                'type'                => $type,
                'base'                => $base,
                'base_amount'         => round($baseAmount, 2),
                'employee_rate'       => $employeeRate,
                'employer_rate'       => $employerRate,
                'amount'              => $employeeAmount,
                'employer_contribution'=> $employerAmount,
                'ceiling'             => $ceiling,
            ];

            $totalEmployee += $employeeAmount;
            $totalEmployer += $employerAmount;

            Log::debug("Statutory deduction calculated: {$name}", [
                'employee_id'     => $employee->id,
                'type'            => $type,
                'base'            => $base,
                'base_amount'     => $baseAmount,
                'employee_amount' => $employeeAmount,
                'employer_amount' => $employerAmount,
            ]);
        }

        return [
            'breakdown'      => $breakdown,
            'total_employee' => $this->applyRounding($totalEmployee),
            'total_employer' => $this->applyRounding($totalEmployer),
        ];
    }

    /**
     * Calculate PAYE tax based on tax bands in config
     */
    public function calculatePAYE(float $taxableIncome): float
    {
        $taxBands          = $this->config_data['taxBands'] ?? [];
        $calculationMethod = $this->getTaxCalculationMethod();

        Log::debug('Calculating PAYE', [
            'taxable_income' => $taxableIncome,
            'method'         => $calculationMethod,
            'bands_count'    => count($taxBands),
        ]);

        if ($calculationMethod === 'cumulative') {
            return $this->calculateCumulativePAYE($taxableIncome, $taxBands);
        }

        return $this->calculateNonCumulativePAYE($taxableIncome, $taxBands);
    }

    /**
     * Calculate non-cumulative PAYE (monthly).
     *
     * FIX: Band boundaries in the config use overlapping limits (e.g. 5100 / 5100.01)
     * to prevent double-counting visually, but using them as raw lower limits causes
     * each band to be K0.01 narrower than intended, producing a cumulative K0.01–K0.03
     * rounding shortfall on the final PAYE figure.
     *
     * Solution: track the previous band's upperLimit and use that as the effective
     * lower boundary for the next band. This guarantees each band is exactly the
     * correct width regardless of how lowerLimit is stored in the config.
     * Each band's tax contribution is also rounded independently before accumulation
     * to prevent floating point drift on rates like 37%.
     */
    private function calculateNonCumulativePAYE(float $monthlyIncome, array $taxBands): float
    {
        if (empty($taxBands)) {
            Log::warning('No tax bands configured');
            return 0.0;
        }

        $tax = 0.0;

        usort($taxBands, fn($a, $b) => ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0));

        // Track the previous band's upper boundary as the effective floor for the
        // next band, avoiding the K0.01 gap caused by config values like 5100/5100.01.
        $prevUpper = 0.0;

        foreach ($taxBands as $band) {
            $upperLimit = isset($band['upperLimit']) ? (float) $band['upperLimit'] : PHP_FLOAT_MAX;
            $rate       = (float) ($band['rate'] ?? 0);
            $lower      = $prevUpper;  // ← effective lower, not config lowerLimit

            if ($monthlyIncome <= $lower) {
                break;
            }

            if ($upperLimit === PHP_FLOAT_MAX) {
                $taxableInBand = $monthlyIncome - $lower;
                $tax += $this->applyRounding(($taxableInBand * $rate) / 100);
                break;
            }

            $taxableInBand = $monthlyIncome > $upperLimit
                ? $upperLimit - $lower
                : $monthlyIncome - $lower;

            $tax      += $this->applyRounding(($taxableInBand * $rate) / 100);
            $prevUpper = $upperLimit;
        }

        return $this->applyRounding($tax);
    }

    /**
     * Calculate cumulative PAYE (annual converted to monthly).
     * Same boundary fix applied for consistency.
     */
    private function calculateCumulativePAYE(float $monthlyIncome, array $taxBands): float
    {
        if (empty($taxBands)) {
            Log::warning('No tax bands configured');
            return 0.0;
        }

        $annualIncome = $monthlyIncome * 12;
        $annualTax    = 0.0;

        usort($taxBands, fn($a, $b) => ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0));

        $prevUpper = 0.0;

        foreach ($taxBands as $band) {
            $upperLimit = isset($band['upperLimit']) ? (float) $band['upperLimit'] : PHP_FLOAT_MAX;
            $rate       = (float) ($band['rate'] ?? 0);
            $lower      = $prevUpper;

            if ($annualIncome <= $lower) {
                break;
            }

            if ($upperLimit === PHP_FLOAT_MAX) {
                $taxableInBand = $annualIncome - $lower;
                $annualTax += $this->applyRounding(($taxableInBand * $rate) / 100);
                break;
            }

            $taxableInBand = $annualIncome > $upperLimit
                ? $upperLimit - $lower
                : $annualIncome - $lower;

            $annualTax += $this->applyRounding(($taxableInBand * $rate) / 100);
            $prevUpper  = $upperLimit;
        }

        return $this->applyRounding($annualTax / 12);
    }

    /**
     * Calculate complete payroll for an employee
     */
    public function calculatePayroll(Employee $employee, float $overtimePay = 0, float $bonuses = 0): array
    {
        $basicSalary = (float) $employee->base_salary;

        // Calculate allowances
        $allowances = $this->calculateAllowances($employee);

        // Calculate gross salary (bonuses included before deductions)
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;

        // Calculate statutory deductions
        $statutory = $this->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);

        // Calculate taxable income
        $taxableIncome = $grossSalary;
        foreach ($statutory['breakdown'] as $deduction) {
            if ($deduction['type'] === 'pension') {
                $taxableIncome -= $deduction['amount'];
            }
        }

        // Calculate PAYE
        $paye = $this->calculatePAYE($taxableIncome);

        // Calculate totals
        $totalDeductions = $paye + $statutory['total_employee'];
        $netSalary       = $grossSalary - $totalDeductions;

        Log::info('Payroll calculation complete', [
            'employee_id'     => $employee->id,
            'basic_salary'    => $basicSalary,
            'gross_salary'    => $grossSalary,
            'taxable_income'  => $taxableIncome,
            'paye'            => $paye,
            'statutory_total' => $statutory['total_employee'],
            'total_deductions'=> $totalDeductions,
            'net_salary'      => $netSalary,
        ]);

        return [
            'basic_salary'   => $basicSalary,
            'allowances'     => $allowances,
            'overtime_pay'   => $overtimePay,
            'bonuses'        => $bonuses,
            'gross_salary'   => $grossSalary,
            'taxable_income' => $taxableIncome,
            'deductions'     => [
                'paye_tax'         => $paye,
                'statutory'        => $statutory['breakdown'],
                'total_statutory'  => $statutory['total_employee'],
                'total_deductions' => $totalDeductions,
            ],
            'employer_costs' => [
                'statutory_contributions' => $statutory['total_employer'],
            ],
            'net_salary' => $netSalary,
        ];
    }

    /**
     * Apply rounding based on config
     */
    private function applyRounding(float $amount): float
    {
        $method = $this->config_data['roundingMethod'] ?? 'nearest';

        return match ($method) {
            'up'    => ceil($amount),
            'down'  => floor($amount),
            'none'  => $amount,
            default => round($amount, 2),
        };
    }
}
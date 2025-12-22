<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function scopeGlobal($query)
    {
        return $query->whereNull('business_id')->active();
    }

    /**
     * Get the appropriate tax configuration for a business and country
     * Priority: Business-specific > Country-specific > Global
     */
    public static function getForBusinessAndCountry(?int $businessId, string $countryCode): ?self
    {
        // Try business-specific config first
        if ($businessId) {
            $config = self::where('business_id', $businessId)
                         ->where('country_code', $countryCode)
                         ->active()
                         ->first();
            
            if ($config) {
                return $config;
            }
        }
        
        // Fall back to country-specific config
        $config = self::whereNull('business_id')
                     ->where('country_code', $countryCode)
                     ->active()
                     ->first();
        
        if ($config) {
            return $config;
        }
        
        // Last resort: global config (if any)
        return self::whereNull('business_id')
                   ->whereNull('country_code')
                   ->active()
                   ->first();
    }

    /**
     * Attribute accessors/mutators
     */
    public function getConfigDataAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setConfigDataAttribute($value)
    {
        $this->attributes['config_data'] = json_encode($value);
    }

    /**
     * Calculate pension deduction (5% of basic pay for full-time employees only)
     */
    public function calculatePension(Employee $employee, float $basicSalary): float
    {
        // Only apply pension deduction to full-time employees
        if ($employee->employment_type !== 'full_time') {
            return 0.0;
        }

        $config = $this->config_data;
        $rate = ($config['pensionRate'] ?? 5) / 100;
        $maxSalary = $config['pensionMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($basicSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib);
    }
     /**
     * Calculate Statutory Deductions (Dynamic)
     * Returns an array of deductions with names and calculated amounts
     */
    public function calculateStatutoryDeductions(Employee $employee, float $basicSalary, float $grossSalary): array
    {
        $config = $this->config_data;
        $deductionsList = $config['statutory_deductions'] ?? [];
        
        $results = [];
        $totalEmployeeDeduction = 0.0;
        $totalEmployerContribution = 0.0;

        foreach ($deductionsList as $item) {

             if (($item['type'] === 'pension') && ($employee->employment_type !== 'full_time')) {
                continue; 
            }
            // Determine the base amount (Basic vs Gross)
            $baseAmount = ($item['base'] === 'basic') ? $basicSalary : $grossSalary;
            
            // Apply Ceiling if it exists
            if (!empty($item['ceiling']) && $item['ceiling'] > 0) {
                $assessableAmount = min($baseAmount, $item['ceiling']);
            } else {
                $assessableAmount = $baseAmount;
            }

            // Calculate Employee Share
            $empRate = ($item['employee_rate'] ?? 0) / 100;
            $empAmount = $this->applyRounding($assessableAmount * $empRate);

            // Calculate Employer Share
            $erRate = ($item['employer_rate'] ?? 0) / 100;
            $erAmount = $this->applyRounding($assessableAmount * $erRate);

            $results[] = [
                'name' => $item['name'], // e.g., "NHIMA" or "NHIF"
                'type' => $item['type'],
                'amount' => $empAmount,
                'employer_contribution' => $erAmount,
                'meta' => [
                    'rate' => $item['employee_rate'],
                    'base_used' => $item['base']
                ]
            ];

            $totalEmployeeDeduction += $empAmount;
            $totalEmployerContribution += $erAmount;
        }

        return [
            'breakdown' => $results,
            'total_employee' => $totalEmployeeDeduction,
            'total_employer' => $totalEmployerContribution
        ];
    }

    /**
     * Calculate progressive PAYE on GROSS salary
     */
    public function calculatePAYE(float $grossSalary): float
    {
        $tax = 0.0;
        $remaining = $grossSalary;
        $bands = $this->config_data['taxBands'] ?? [];
        
        usort($bands, function($a, $b) {
            return ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0);
        });

        foreach ($bands as $band) {
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
        
        return $this->applyRounding($tax);
    }

    /**
     * Calculate NAPSA employee contribution (5% of gross, capped)
     */
    public function calculateNAPSA(float $grossSalary): float
    {
        $config = $this->config_data;
        $rate = ($config['napsaRate'] ?? 5) / 100;
        $maxSalary = $config['napsaMaxSalary'] ?? 34164.00;
        $maxContrib = 1708.20;
        
        $assessable = min($grossSalary, $maxSalary);
        $contrib = min($assessable * $rate, $maxContrib);
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate NHIMA employee contribution (1% of basic)
     */
    public function calculateNHIMA(float $basicSalary): float
    {
        $config = $this->config_data;
        $rate = ($config['nhimaEmployeeRate'] ?? 1) / 100;
        $maxSalary = $config['nhimaMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($basicSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate allowances from employee data
     */
    public function calculateAllowances(Employee $employee): array
    {
        $basicSalary = $employee->base_salary;
        $housing = $basicSalary * 0.25;
        $transport = $employee->transport_allowance ?? 0.00;
        $lunch = $employee->lunch_allowance ?? 0.00;
        
        return [
            'housing' => $this->applyRounding($housing),
            'transport' => $this->applyRounding($transport),
            'lunch' => $this->applyRounding($lunch),
            'total' => $this->applyRounding($housing + $transport + $lunch)
        ];
    }

     /**
     * Updated Calculate Payroll
     */
    public function calculatePayroll(Employee $employee, float $overtimePay = 0, float $bonuses = 0): array
    {
        $basicSalary = $employee->base_salary;
        $allowances = $this->calculateAllowances($employee); // assuming this exists
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;
        
        // 1. Calculate Statutory Deductions (Dynamic)
        $statutory = $this->calculateStatutoryDeductions($employee, $basicSalary, $grossSalary);

        // 2. Calculate PAYE
        // Note: Check country laws. Some deductions (like Pension) reduce taxable income before PAYE.
        // For simplicity here, we assume Gross is Taxable, but you might need a flag in deduction config: "is_tax_deductible"
        $taxableIncome = $grossSalary; 
        
        // Example: If pension is tax deductible (common in many countries)
        foreach($statutory['breakdown'] as $deduction) {
            if($deduction['type'] === 'pension') {
                $taxableIncome -= $deduction['amount'];
            }
        }

        $paye = $this->calculatePAYE($taxableIncome);

        // 3. Totals
        $totalDeductions = $paye + $statutory['total_employee'];
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'basic_salary' => $basicSalary,
            'gross_salary' => $grossSalary,
            'taxable_income' => $taxableIncome,
            'deductions' => [
                'paye_tax' => $paye,
                'statutory' => $statutory['breakdown'], // Dynamic list
                'total_deductions' => $totalDeductions,
            ],
            'employer_costs' => [
                'statutory_contributions' => $statutory['total_employer']
            ],
            'net_salary' => $netSalary
        ];
    }
    /**
     * Apply rounding method
     */
    private function applyRounding(float $amount): float
    {
        $method = $this->config_data['roundingMethod'] ?? 'nearest';
        return match($method) {
            'up' => ceil($amount),
            'down' => floor($amount),
            'none' => $amount,
            default => round($amount, 2),
        };
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Payslip extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'pay_period_start',
        'pay_period_end',
        'payment_date',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'other_allowances',
        'overtime_hours',
        'overtime_rate',
        'overtime_pay',
        'bonuses',
        'gross_salary',
        
        // Legacy fields for backward compatibility
        'napsa',
        'paye',
        'nhima',
        'pension',
        
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'pdf_path',
        'is_sent',
        'breakdown', // Primary storage for dynamic deductions
    ];
    
    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'house_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'napsa' => 'decimal:2',
        'paye' => 'decimal:2',
        'nhima' => 'decimal:2',
        'pension' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'breakdown' => 'array',
        'status' => 'string',
        'pdf_path' => 'string',
        'is_sent' => 'boolean',
        'payroll_id' => 'integer',
    ];
    
    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
    
    /**
     * Scopes
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('pay_period_start', [$startDate, $endDate]);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }
    
    public function scopeByBusiness($query, $businessId)
    {
        return $query->whereHas('employee', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }
     public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('employee', function ($q) use ($countryId) {
            $q->where('country_id', $countryId);
        });
    }
    
    
    /**
     * Accessors & Computed Properties
     */
    
    /**
     * Get total earnings (gross salary)
     */
    public function getTotalEarningsAttribute()
    {
        return $this->gross_salary;
    }
    
    /**
     * Get dynamic statutory deductions from breakdown
     * This is the primary source of truth for deductions
     */
    public function getStatutoryDeductionsAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        return $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];
    }
    
    /**
     * Get all allowances as an array
     */
    public function getAllowancesArrayAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        $allowances = $breakdown['earnings_breakdown']['allowances'] ?? [];
        
        return [
            'housing' => $allowances['housing'] ?? $this->house_allowance ?? 0,
            'transport' => $allowances['transport'] ?? $this->transport_allowance ?? 0,
            'lunch' => $allowances['lunch'] ?? $this->other_allowances ?? 0,
            'total' => $allowances['total'] ?? 
                ($this->house_allowance + $this->transport_allowance + $this->other_allowances),
        ];
    }
    
    /**
     * Get all deductions with dynamic statutory breakdown
     */
    public function getDeductionsArrayAttribute(): array
    {
        $statutory = $this->statutory_deductions;
        
        return [
            'paye' => $this->paye,
            'statutory' => $statutory,
            'statutory_total' => collect($statutory)->sum('amount'),
            'other_deductions' => $this->other_deductions,
            'total' => $this->total_deductions,
        ];
    }
    

    /**
     * Extract all deductions as a flat array with names and amounts
     * This is used for dynamic report generation
     */
    public function getAllDeductionsFlat(): array
    {
        $deductions = [];
        
        // Add PAYE tax
        if ($this->paye > 0) {
            $deductions['PAYE Tax'] = [
                'amount' => (float) $this->paye,
                'type' => 'tax',
                'name' => 'PAYE Tax'
            ];
        }
        
        // Add statutory deductions from breakdown
        $statutory = $this->statutory_deductions;
        foreach ($statutory as $deduction) {
            $name = $deduction['name'];
            $deductions[$name] = [
                'amount' => (float) ($deduction['amount'] ?? 0),
                'type' => $deduction['type'] ?? 'statutory',
                'name' => $name
            ];
        }
        
        // Add legacy fields if not in statutory (backward compatibility)
        if ($this->napsa > 0 && !isset($deductions['NAPSA'])) {
            $deductions['NAPSA'] = [
                'amount' => (float) $this->napsa,
                'type' => 'levy',
                'name' => 'NAPSA'
            ];
        }
        
        if ($this->nhima > 0 && !isset($deductions['NHIMA'])) {
            $deductions['NHIMA'] = [
                'amount' => (float) $this->nhima,
                'type' => 'health',
                'name' => 'NHIMA'
            ];
        }
        
        if ($this->pension > 0 && !isset($deductions['Pension'])) {
            $deductions['Pension'] = [
                'amount' => (float) $this->pension,
                'type' => 'pension',
                'name' => 'Pension'
            ];
        }
        
        // Add other deductions
        if ($this->other_deductions > 0) {
            $deductions['Other Deductions'] = [
                'amount' => (float) $this->other_deductions,
                'type' => 'other',
                'name' => 'Other Deductions'
            ];
        }
        
        return $deductions;
    }

    /**
     * Get deduction by type from statutory breakdown
     */
    public function getDeductionByType(string $type): ?array
    {
        $statutory = $this->statutory_deductions;
        
        foreach ($statutory as $deduction) {
            if ($deduction['type'] === $type) {
                return $deduction;
            }
        }
        
        return null;
    }
    
    /**
     * Get deduction amount by type name
     */
    public function getDeductionAmount(string $deductionName): float
    {
        $deductions = $this->getAllDeductionsFlat();
        return $deductions[$deductionName]['amount'] ?? 0.0;
    }

    /**
     * Get deduction by name from statutory breakdown
     */
    public function getDeductionByName(string $name): ?array
    {
        $statutory = $this->statutory_deductions;
        
        foreach ($statutory as $deduction) {
            if (stripos($deduction['name'], $name) !== false) {
                return $deduction;
            }
        }
        
        return null;
    }
    
    /**
     * Get total statutory deductions
     */
    public function getTotalStatutoryDeductionsAttribute(): float
    {
        $statutory = $this->statutory_deductions;
        return (float) collect($statutory)->sum('amount');
    }
    
    /**
     * Get NAPSA deduction (backward compatibility)
     * Tries breakdown first, falls back to legacy column
     */
    public function getNapsaAmountAttribute(): float
    {
        $napsa = $this->getDeductionByName('NAPSA');
        if ($napsa && $napsa['type'] === 'levy') {
            return (float) $napsa['amount'];
        }
        return (float) $this->napsa;
    }
    
    /**
     * Get NHIMA deduction (backward compatibility)
     */
    public function getNhimaAmountAttribute(): float
    {
        $nhima = $this->getDeductionByType('health');
        if ($nhima) {
            return (float) $nhima['amount'];
        }
        return (float) $this->nhima;
    }
    
    /**
     * Get Pension deduction (backward compatibility)
     */
    public function getPensionAmountAttribute(): float
    {
        $statutory = $this->statutory_deductions;
        
        // Sum all pension-type deductions excluding NAPSA
        $pensionTotal = 0;
        foreach ($statutory as $deduction) {
            if ($deduction['type'] === 'pension' && stripos($deduction['name'], 'NAPSA') === false) {
                $pensionTotal += (float) $deduction['amount'];
            }
        }
        
        return $pensionTotal ?: (float) $this->pension;
    }
    
    /**
     * Get tax configuration used for this payslip
     */
    public function getTaxConfigAttribute(): ?array
    {
        $breakdown = $this->breakdown ?? [];
        
        if (isset($breakdown['tax_config_id'])) {
            return [
                'id' => $breakdown['tax_config_id'],
                'type' => $breakdown['tax_config_type'] ?? 'unknown',
                'country' => $breakdown['tax_config_country'] ?? null,
                'business_id' => $breakdown['tax_config_business_id'] ?? null,
                'currency' => $breakdown['currency'] ?? 'USD',
            ];
        }
        
        return null;
    }
    
    /**
     * Get currency from breakdown or default
     */
    public function getCurrencyAttribute(): string
    {
        $breakdown = $this->breakdown ?? [];
        return $breakdown['currency'] ?? 'ZMW';
    }
    
    /**
     * Get employer contributions from breakdown
     */
    public function getEmployerContributionsAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        $statutory = $breakdown['deductions_breakdown']['statutory_breakdown'] ?? [];
        
        $contributions = [];
        $total = 0;
        
        foreach ($statutory as $deduction) {
            if (isset($deduction['employer_contribution']) && $deduction['employer_contribution'] > 0) {
                $contributions[] = [
                    'name' => $deduction['name'],
                    'type' => $deduction['type'],
                    'amount' => $deduction['employer_contribution'],
                ];
                $total += $deduction['employer_contribution'];
            }
        }
        
        return [
            'breakdown' => $contributions,
            'total' => round($total, 2),
        ];
    }
    
    /**
     * Check if payslip uses dynamic tax configuration
     */
    public function usesDynamicTaxConfig(): bool
    {
        $breakdown = $this->breakdown ?? [];
        return isset($breakdown['tax_config_id']);
    }
    
    /**
     * Get complete earnings breakdown
     */
    public function getEarningsBreakdownAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        $earnings = $breakdown['earnings_breakdown'] ?? [];
        
        return [
            'basic_salary' => $earnings['basic_salary'] ?? $this->basic_salary,
            'allowances' => $earnings['allowances'] ?? $this->allowances_array,
            'overtime' => $earnings['overtime'] ?? [
                'pay' => $this->overtime_pay,
                'hours' => $this->overtime_hours,
            ],
            'bonuses' => $earnings['bonuses'] ?? $this->bonuses ?? 0,
            'gross_total' => $earnings['gross_total'] ?? $this->gross_salary,
        ];
    }
    
    /**
     * Format payslip for detailed report
     */
    public function toReportArray(): array
    {
        $employee = $this->employee;
        $deductions = $this->getAllDeductionsFlat();
        
        return [
            'employee_id' => $this->employee_id,
            'employee_name' => $employee->user 
                ? ($employee->user->first_name . ' ' . $employee->user->last_name)
                : 'N/A',
            'business' => $employee->business ? $employee->business->name : 'No Business',
            'business_id' => $employee->business_id,
            'country' => $employee->country ? $employee->country->name : 'N/A',
            'country_code' => $employee->country ? $employee->country->code : null,
            'department' => $employee->department ?? 'Unassigned',
            'pay_period' => $this->pay_period_start 
                ? $this->pay_period_start->format('M d, Y') . ' - ' . $this->pay_period_end->format('M d, Y')
                : 'N/A',
            'gross_salary' => (float) $this->gross_salary,
            'net_salary' => (float) $this->net_pay,
            'total_deductions' => (float) $this->total_deductions,
            'deductions' => $deductions,
            'currency' => $this->currency,
            'status' => $this->status ?? 'pending',
        ];
    }
    /**
     * Format payslip for API response
     */
    public function toDetailedArray(): array
    {
        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee->id,
                'employee_id' => $this->employee->employee_id,
                'name' => $this->employee->user->first_name . ' ' . $this->employee->user->last_name,
                'department' => $this->employee->department,
                'employment_type' => $this->employee->employment_type,
            ],
            'business' => [
                'id' => $this->employee->business_id,
                'name' => $this->employee->business->name ?? 'N/A',
            ],
            'period' => [
                'start' => $this->pay_period_start?->format('Y-m-d'),
                'end' => $this->pay_period_end?->format('Y-m-d'),
                'payment_date' => $this->payment_date?->format('Y-m-d'),
            ],
            'earnings' => $this->earnings_breakdown,
            'deductions' => [
                'paye' => $this->paye,
                'statutory' => $this->statutory_deductions,
                'statutory_total' => $this->total_statutory_deductions,
                'other_deductions' => $this->other_deductions,
                'total_deductions' => $this->total_deductions,
            ],
            'employer_contributions' => $this->employer_contributions,
            'summary' => [
                'gross_salary' => $this->gross_salary,
                'total_deductions' => $this->total_deductions,
                'net_pay' => $this->net_pay,
            ],
            'tax_config' => $this->tax_config,
            'currency' => $this->currency,
            'status' => $this->status,
            'pdf_available' => !empty($this->pdf_path),
            'pdf_path' => $this->pdf_path,
            'is_sent' => $this->is_sent,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
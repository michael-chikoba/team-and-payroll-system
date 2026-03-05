<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasEncryptedFields;

class Payslip extends Model
{
    use HasFactory;
    use HasEncryptedFields;

    protected $table = 'payslips';

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
        'gross_pay',
        'tax_deductions',
        'napsa',
        'paye',
        'nhima',
        'pension',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'breakdown',
        'pdf_path',
        'is_sent',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'payment_date' => 'date',
        'breakdown' => 'array',
        'is_sent' => 'boolean',
        // Note: All financial fields are encrypted via trait, not cast here
    ];

    protected $appends = [
        'total_earnings',
        'deductions_array',
        'statutory_deductions',
        'allowances_array',
        'total_statutory_deductions',
        'napsa_amount',
        'nhima_amount',
        'pension_amount',
        'currency',
    ];

    /**
     * Define which fields should be encrypted
     */
    public function getEncryptedFields(): array
    {
        return [
            // All financial fields - these will be encrypted in DB
            'basic_salary',
            'house_allowance',
            'transport_allowance',
            'other_allowances',
            'overtime_rate',
            'overtime_pay',
            'gross_salary',
            'gross_pay',
            'tax_deductions',
            'napsa',
            'paye',
            'nhima',
            'pension',
            'other_deductions',
            'total_deductions',
            'net_pay',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the employee that owns this payslip
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll batch that contains this payslip
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope query to payslips for a specific period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('pay_period_start', [$startDate, $endDate]);
    }
    
    /**
     * Scope query to payslips by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope query to payslips by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }
    
    /**
     * Scope query to payslips by business
     */
    public function scopeByBusiness($query, $businessId)
    {
        return $query->whereHas('employee', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }
    
    /**
     * Scope query to payslips by country
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('employee', function ($q) use ($countryId) {
            $q->where('country_id', $countryId);
        });
    }
    
    /**
     * Scope query to payslips that are sent
     */
    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }
    
    /**
     * Scope query to payslips that are not sent
     */
    public function scopeNotSent($query)
    {
        return $query->where('is_sent', false);
    }

    // =========================================================================
    // ACCESSORS & COMPUTED PROPERTIES
    // =========================================================================

    /**
     * Get total earnings (gross salary) - uses trait decryption
     */
    public function getTotalEarningsAttribute(): float
    {
        return (float) ($this->gross_salary ?? 0);
    }
    
    /**
     * Get all deductions as a simple array
     */
    public function getDeductionsArrayAttribute(): array
    {
        return [
            'paye' => (float) ($this->paye ?? 0),
            'napsa' => (float) ($this->napsa ?? 0),
            'nhima' => (float) ($this->nhima ?? 0),
            'pension' => (float) ($this->pension ?? 0),
            'other_deductions' => (float) ($this->other_deductions ?? 0),
            'total' => (float) ($this->total_deductions ?? 0),
        ];
    }

    /**
     * Get employer contributions from breakdown
     */
    public function getEmployerContributionsAttribute(): array
    {
        $breakdown = $this->breakdown ?? [];
        $employerTotal = $breakdown['deductions_breakdown']['employer_total'] ?? 0;
        
        return [
            'total' => round((float) $employerTotal, 2),
        ];
    }
    
    /**
     * Get dynamic statutory deductions from breakdown
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
            'housing' => (float) ($allowances['housing'] ?? $this->house_allowance ?? 0),
            'transport' => (float) ($allowances['transport'] ?? $this->transport_allowance ?? 0),
            'lunch' => (float) ($allowances['lunch'] ?? $this->other_allowances ?? 0),
            'total' => (float) ($allowances['total'] ?? 
                (($this->house_allowance ?? 0) + ($this->transport_allowance ?? 0) + ($this->other_allowances ?? 0))),
        ];
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
     */
    public function getNapsaAmountAttribute(): float
    {
        $napsa = $this->getDeductionByName('NAPSA');
        if ($napsa && ($napsa['type'] ?? '') === 'levy') {
            return (float) ($napsa['amount'] ?? 0);
        }
        return (float) ($this->napsa ?? 0);
    }
    
    /**
     * Get NHIMA deduction (backward compatibility)
     */
    public function getNhimaAmountAttribute(): float
    {
        $nhima = $this->getDeductionByType('health');
        if ($nhima) {
            return (float) ($nhima['amount'] ?? 0);
        }
        return (float) ($this->nhima ?? 0);
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
            if (($deduction['type'] ?? '') === 'pension' && stripos($deduction['name'] ?? '', 'NAPSA') === false) {
                $pensionTotal += (float) ($deduction['amount'] ?? 0);
            }
        }
        
        return $pensionTotal ?: (float) ($this->pension ?? 0);
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
     * Check if payslip uses dynamic tax configuration
     */
    public function getUsesDynamicTaxConfigAttribute(): bool
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
            'basic_salary' => (float) ($earnings['basic_salary'] ?? $this->basic_salary ?? 0),
            'allowances' => $earnings['allowances'] ?? $this->allowances_array,
            'overtime' => $earnings['overtime'] ?? [
                'pay' => (float) ($this->overtime_pay ?? 0),
                'hours' => (float) ($this->overtime_hours ?? 0),
            ],
            'bonuses' => (float) ($earnings['bonuses'] ?? $this->bonuses ?? 0),
            'gross_total' => (float) ($earnings['gross_total'] ?? $this->gross_salary ?? 0),
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Extract all deductions as a flat array with names and amounts
     */
    public function getAllDeductionsFlat(): array
    {
        $deductions = [];
        
        // Add PAYE tax
        if (($this->paye ?? 0) > 0) {
            $deductions['PAYE Tax'] = [
                'amount' => (float) $this->paye,
                'type' => 'tax',
                'name' => 'PAYE Tax'
            ];
        }
        
        // Add statutory deductions from breakdown
        $statutory = $this->statutory_deductions;
        foreach ($statutory as $deduction) {
            $name = $deduction['name'] ?? 'Unknown';
            $deductions[$name] = [
                'amount' => (float) ($deduction['amount'] ?? 0),
                'type' => $deduction['type'] ?? 'statutory',
                'name' => $name
            ];
        }
        
        // Add legacy fields if not in statutory (backward compatibility)
        if (($this->napsa ?? 0) > 0 && !isset($deductions['NAPSA'])) {
            $deductions['NAPSA'] = [
                'amount' => (float) $this->napsa,
                'type' => 'levy',
                'name' => 'NAPSA'
            ];
        }
        
        if (($this->nhima ?? 0) > 0 && !isset($deductions['NHIMA'])) {
            $deductions['NHIMA'] = [
                'amount' => (float) $this->nhima,
                'type' => 'health',
                'name' => 'NHIMA'
            ];
        }
        
        if (($this->pension ?? 0) > 0 && !isset($deductions['Pension'])) {
            $deductions['Pension'] = [
                'amount' => (float) $this->pension,
                'type' => 'pension',
                'name' => 'Pension'
            ];
        }
        
        // Add other deductions
        if (($this->other_deductions ?? 0) > 0) {
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
            if (($deduction['type'] ?? '') === $type) {
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
        return (float) ($deductions[$deductionName]['amount'] ?? 0.0);
    }

    /**
     * Get deduction by name from statutory breakdown
     */
    public function getDeductionByName(string $name): ?array
    {
        $statutory = $this->statutory_deductions;
        
        foreach ($statutory as $deduction) {
            if (stripos($deduction['name'] ?? '', $name) !== false) {
                return $deduction;
            }
        }
        
        return null;
    }

    // =========================================================================
    // FORMATTING METHODS
    // =========================================================================

    /**
     * Format payslip for detailed API response
     */
    public function toDetailedArray(): array
    {
        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee->id ?? null,
                'employee_id' => $this->employee->employee_id ?? null,
                'name' => $this->employee->full_name ?? 'N/A',
                'department' => $this->employee->department ?? 'Unassigned',
            ],
            'period' => [
                'start' => $this->pay_period_start?->format('Y-m-d'),
                'end' => $this->pay_period_end?->format('Y-m-d'),
                'payment_date' => $this->payment_date?->format('Y-m-d'),
            ],
            // These will auto-decrypt via trait
            'basic_salary' => (float) ($this->basic_salary ?? 0),
            'gross_salary' => (float) ($this->gross_salary ?? 0),
            'net_pay' => (float) ($this->net_pay ?? 0),
            'total_deductions' => (float) ($this->total_deductions ?? 0),
            'tax_deductions' => (float) ($this->tax_deductions ?? 0),
            
            // These will also auto-decrypt
            'house_allowance' => (float) ($this->house_allowance ?? 0),
            'transport_allowance' => (float) ($this->transport_allowance ?? 0),
            'other_allowances' => (float) ($this->other_allowances ?? 0),
            'overtime' => [
                'hours' => (float) ($this->overtime_hours ?? 0),
                'rate' => (float) ($this->overtime_rate ?? 0),
                'pay' => (float) ($this->overtime_pay ?? 0),
            ],
            'bonuses' => (float) ($this->bonuses ?? 0),
            'deductions' => [
                'napsa' => (float) ($this->napsa ?? 0),
                'paye' => (float) ($this->paye ?? 0),
                'nhima' => (float) ($this->nhima ?? 0),
                'pension' => (float) ($this->pension ?? 0),
                'other' => (float) ($this->other_deductions ?? 0),
            ],
            'breakdown' => $this->breakdown,
            'pdf_path' => $this->pdf_path,
            'status' => $this->status ?? 'pending',
            'is_sent' => (bool) ($this->is_sent ?? false),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $employee && $employee->user 
                ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->last_name ?? ''))
                : 'N/A',
            'business' => $employee && $employee->business ? $employee->business->name : 'No Business',
            'business_id' => $employee->business_id ?? null,
            'country' => $employee && $employee->country ? $employee->country->name : 'N/A',
            'country_code' => $employee && $employee->country ? $employee->country->code : null,
            'department' => $employee->department ?? 'Unassigned',
            'pay_period' => $this->pay_period_start && $this->pay_period_end
                ? $this->pay_period_start->format('M d, Y') . ' - ' . $this->pay_period_end->format('M d, Y')
                : 'N/A',
            'gross_salary' => (float) ($this->gross_salary ?? 0),
            'net_salary' => (float) ($this->net_pay ?? 0),
            'total_deductions' => (float) ($this->total_deductions ?? 0),
            'deductions' => $deductions,
            'currency' => $this->currency,
            'status' => $this->status ?? 'pending',
            'is_sent' => (bool) ($this->is_sent ?? false),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    /**
     * Mark payslip as sent
     */
    public function markAsSent(): bool
    {
        $this->is_sent = true;
        return $this->save();
    }

    /**
     * Generate PDF for this payslip
     */
    public function generatePdf(): ?string
    {
        // This would be implemented by your PDF generation service
        // Return the path to the generated PDF
        return null;
    }
}
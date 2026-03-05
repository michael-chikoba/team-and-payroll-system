<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasEncryptedFields;

class Payroll extends Model
{
    use HasFactory;
    use HasEncryptedFields;

    protected $fillable = [
        'payroll_period',
        'start_date',
        'end_date',
        'status',
        'total_gross',
        'total_net',
        'employee_count',
        'processed_at',
        'business_id',
        'country_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'processed_at' => 'datetime',
        // REMOVED decimal casts for total_gross and total_net
        // They will be encrypted by the trait
    ];

    /**
     * Define which fields should be encrypted
     */
    public function getEncryptedFields(): array
    {
        return [
            'total_gross',    // Encrypted
            'total_net',      // Encrypted
            
        ];
    }
    /**
     * Get payslips associated with this payroll batch
     */
    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Get business if the payroll is business-scoped
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get country if the payroll is country-scoped
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope query to completed payrolls only
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope query to processing payrolls
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope query to draft payrolls
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope query to current month payrolls
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('processed_at', now()->month)
                     ->whereYear('processed_at', now()->year);
    }

    /**
     * Scope query to specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        if ($businessId) {
            return $query->where('business_id', $businessId);
        }
        return $query;
    }

    /**
     * Mark payroll as completed
     */
    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * Mark payroll as processing
     */
    public function markAsProcessing()
    {
        $this->status = 'processing';
        return $this->save();
    }

    /**
     * Check if payroll is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payroll is processing
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Check if payroll is draft
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Get all unique employees in this payroll batch
     */
    public function employees()
    {
        return Employee::whereIn('id', $this->payslips()->pluck('employee_id'));
    }

    /**
     * Calculate summary statistics for this payroll
     */
    public function calculateSummary()
    {
        $payslips = $this->payslips;
        
        return [
            'employee_count' => $payslips->count(),
            'total_gross' => $payslips->sum('gross_pay'),
            'total_net' => $payslips->sum('net_pay'),
            'total_deductions' => $payslips->sum('total_deductions'),
            'total_paye' => $payslips->sum('paye_tax'),
        ];
    }

    /**
     * Update summary fields based on payslips
     */
    public function updateSummary()
    {
        $summary = $this->calculateSummary();
        
        $this->update([
            'employee_count' => $summary['employee_count'],
            'total_gross' => $summary['total_gross'],
            'total_net' => $summary['total_net'],
        ]);

        return $this;
    }
}
<?php
// Updated Payslip model with pension field
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'gross_salary',
        'napsa',
        'paye',
        'nhima',
        'pension',              // ADDED: Pension deduction field
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'pdf_path',
        'is_sent',
        'breakdown',
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
        'gross_salary' => 'decimal:2',
        'napsa' => 'decimal:2',
        'paye' => 'decimal:2',
        'nhima' => 'decimal:2',
        'pension' => 'decimal:2',        // ADDED: Cast pension as decimal
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
     * Get the employee that owns the payslip
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    /**
     * Get the payroll that owns the payslip
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
    
    /**
     * Scope to filter by pay period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('pay_period_start', [$startDate, $endDate]);
    }
    
    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope to filter by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->whereHas('employee', function ($q) use ($department) {
            $q->where('department', $department);
        });
    }
    
    /**
     * Get total earnings (gross salary)
     */
    public function getTotalEarningsAttribute()
    {
        return $this->gross_salary;
    }
    
    /**
     * Get all deductions as an array
     */
    public function getDeductionsArrayAttribute()
    {
        return [
            'paye' => $this->paye,
            'napsa' => $this->napsa,
            'nhima' => $this->nhima,
            'pension' => $this->pension,
            'other_deductions' => $this->other_deductions,
            'total' => $this->total_deductions,
        ];
    }
}
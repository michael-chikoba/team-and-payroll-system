<?php
// Updated Payslip model with missing fields for database compatibility
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
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'breakdown' => 'array',
        'status' => 'string',
        'pdf_path' => 'string',
        'is_sent' => 'boolean',
        'payroll_id' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
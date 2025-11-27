<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manager_id',
        'employee_id',
        'position',
        'department',
        'base_salary',
        'transport_allowance',
        'lunch_allowance',
        'hire_date',
        'employment_type',
        'bank_details',
        'phone',
        'date_of_birth',
        'national_id',
        'address',
        'emergency_contact',
        'profile_pic', // Added for profile picture storage path
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'lunch_allowance' => 'decimal:2',
        'hire_date' => 'date',
        'bank_details' => 'array',
        'date_of_birth' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically assign a manager if not provided during creation
        static::creating(function ($employee) {
            if (empty($employee->manager_id)) {
                $manager = Manager::where('department', $employee->department)
                    ->first();
                if ($manager) {
                    $employee->manager_id = $manager->user_id;
                }
            }
        });

        // Update manager assignment when department changes
        static::updating(function ($employee) {
            if ($employee->isDirty('department') && empty($employee->manager_id)) {
                $manager = Manager::where('department', $employee->department)
                    ->first();
                if ($manager) {
                    $employee->manager_id = $manager->user_id;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function getCurrentLeaveBalance($type)
    {
        $balance = $this->leaveBalances()
            ->where('type', $type)
            ->first();

        return $balance ? $balance->balance : 0;
    }

    /**
     * Get the full name of the employee
     */
    public function getFullNameAttribute()
    {
        return $this->user ? trim($this->user->first_name . ' ' . $this->user->last_name) : 'N/A';
    }

    /**
     * Get the email of the employee
     */
    public function getEmailAttribute()
    {
        return $this->user ? $this->user->email : 'N/A';
    }

    // In app/Models/Employee.php, add:
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function advances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function activeAdvances()
    {
        return $this->advances()->where('status', 'active')->where('balance', '>', 0);
    }

    public function activeLoans()
    {
        return $this->loans()->where('status', 'active')->where('balance', '>', 0);
    }

    public function hasActiveDeductions()
    {
        return $this->activeAdvances()->exists() || $this->activeLoans()->exists();
    }

    public function getTotalMonthlyDeductions()
    {
        $advanceDeductions = $this->activeAdvances()->sum('monthly_deduction');
        $loanDeductions = $this->activeLoans()->sum('monthly_deduction');

        return $advanceDeductions + $loanDeductions;
    }
}
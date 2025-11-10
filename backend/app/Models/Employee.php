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
        'hire_date',
        'employment_type',
        'bank_details',
    ];
    

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hire_date' => 'date',
        'bank_details' => 'array',
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
}
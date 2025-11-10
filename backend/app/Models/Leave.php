<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'manager_id',
        'type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'manager_notes',
        'attachment_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'integer',
    ];

    // Valid leave types - single source of truth
    public const VALID_TYPES = [
        'annual',
        'sick',
        'maternity',
        'paternity',
        'bereavement',
        'unpaid',
    ];

    // Valid status values
    public const VALID_STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    // Ensure type is always lowercase and valid
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtolower($value),
            set: fn ($value) => strtolower($value),
        );
    }

    // Ensure status is always lowercase and valid
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtolower($value),
            set: fn ($value) => strtolower($value),
        );
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function calculateTotalDays()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
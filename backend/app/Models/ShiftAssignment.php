<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ShiftAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'assigned_by',
        'business_id',
        'country_id',
        'department_id',
        'shift_date',
        'shift_type',
        'start_time',
        'end_time',
        'status',
        'notes',
        'rejection_reason',
        'accepted_at',
        'rejected_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shift_date' => 'date:Y-m-d', // Format without timezone conversion
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * The attributes that should NOT be cast to Carbon instances
     * to avoid timezone conversion issues.
     */
    protected $dates = [
        'accepted_at',
        'rejected_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the employee associated with the shift assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who assigned this shift (manager/admin).
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the business this assignment belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the department this assignment belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the shift template (if applicable).
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Get start_time attribute - prevent timezone conversion
     */
    public function getStartTimeAttribute($value)
    {
        // Return raw time value without timezone conversion
        return $value;
    }

    /**
     * Get end_time attribute - prevent timezone conversion
     */
    public function getEndTimeAttribute($value)
    {
        // Return raw time value without timezone conversion
        return $value;
    }

    /**
     * Set start_time attribute - ensure correct format
     */
    public function setStartTimeAttribute($value)
    {
        // Ensure time is stored in H:i:s format
        if ($value && strlen($value) === 5) {
            $value .= ':00';
        }
        $this->attributes['start_time'] = $value;
    }

    /**
     * Set end_time attribute - ensure correct format
     */
    public function setEndTimeAttribute($value)
    {
        // Ensure time is stored in H:i:s format
        if ($value && strlen($value) === 5) {
            $value .= ':00';
        }
        $this->attributes['end_time'] = $value;
    }

    /**
     * Accept the shift assignment.
     */
    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => Carbon::now()
        ]);
    }

    /**
     * Reject the shift assignment.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => Carbon::now()
        ]);
    }

    /**
     * Cancel the shift assignment.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    /**
     * Mark the shift as completed.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed'
        ]);
    }

    /**
     * Scope to get pending assignments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted assignments.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get upcoming assignments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', Carbon::today());
    }

    /**
     * Scope to get assignments for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('shift_date', $date);
    }

    /**
     * Scope to get assignments for a specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Convert model to array with proper time formatting
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Ensure times are in correct format without timezone conversion
        if (isset($array['start_time'])) {
            $array['start_time'] = substr($array['start_time'], 0, 8);
        }
        if (isset($array['end_time'])) {
            $array['end_time'] = substr($array['end_time'], 0, 8);
        }
        
        return $array;
    }
}
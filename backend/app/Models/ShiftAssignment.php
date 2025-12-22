<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'assigned_by', // Updated from created_by to match migration
    'business_id',
    'country_id',
    'department_id',
    'shift_date', // MATCHES DB
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
        'shift_date' => 'date', // MATCHES DB
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the employee associated with the shift assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user (manager/admin) who created the assignment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
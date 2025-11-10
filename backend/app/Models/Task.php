<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'assigned_to', // This stores user_id
        'created_by',  // This stores user_id
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    /**
     * Get the user this task is assigned to
     * assigned_to field contains user_id
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task
     * created_by field contains user_id
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all comments for this task
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * Scope to get tasks assigned to a specific user
     * @param $query
     * @param int $userId - The user_id from users table
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get tasks created by a specific user
     * @param $query
     * @param int $userId - The user_id from users table
     */
    public function scopeCreatedByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get the employee record of the assigned user
     * This is a convenience method to access employee details
     */
    public function assignedEmployee()
    {
        return $this->hasOneThrough(
            Employee::class,
            User::class,
            'id',          // Foreign key on users table
            'user_id',     // Foreign key on employees table
            'assigned_to', // Local key on tasks table (contains user_id)
            'id'           // Local key on users table
        );
    }

    /**
     * Get the employee record of the user who created the task
     */
    public function creatorEmployee()
    {
        return $this->hasOneThrough(
            Employee::class,
            User::class,
            'id',         // Foreign key on users table
            'user_id',    // Foreign key on employees table
            'created_by', // Local key on tasks table (contains user_id)
            'id'          // Local key on users table
        );
    }
}
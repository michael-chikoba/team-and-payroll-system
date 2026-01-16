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
        'assigned_to',
        'created_by',
        'deadline',
        'tags',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'tags' => 'array',
    ];

    /**
     * Boot method to add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Log task creation
        static::created(function ($task) {
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => $task->created_by,
                'type' => 'created',
                'action' => 'created this task',
            ]);
        });

        // Log task updates
        static::updated(function ($task) {
            $changes = $task->getChanges();
            unset($changes['updated_at']); // Ignore updated_at changes

            foreach ($changes as $field => $newValue) {
                $oldValue = $task->getOriginal($field);
                
                $type = 'updated';
                $action = "updated $field";

                if ($field === 'status') {
                    $type = 'status_change';
                    $action = "changed status from $oldValue to $newValue";
                } elseif ($field === 'priority') {
                    $type = 'priority_change';
                    $action = "changed priority from $oldValue to $newValue";
                } elseif ($field === 'assigned_to') {
                    $type = 'assigned';
                    $oldUser = User::find($oldValue);
                    $newUser = User::find($newValue);
                    $action = "reassigned from " . ($oldUser ? $oldUser->first_name . ' ' . $oldUser->last_name : 'Unknown') 
                            . " to " . ($newUser ? $newUser->first_name . ' ' . $newUser->last_name : 'Unknown');
                }

                TaskHistory::create([
                    'task_id' => $task->id,
                    'user_id' => auth()->id() ?? $task->created_by,
                    'type' => $type,
                    'action' => $action,
                    'field_changed' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        });

        // Log task deletion
        static::deleting(function ($task) {
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => auth()->id() ?? $task->created_by,
                'type' => 'deleted',
                'action' => 'deleted this task',
            ]);
        });
    }

    // Relationships
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class)->orderBy('order');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(TaskWorkLog::class)->latest();
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->latest();
    }

    public function links(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'task_id');
    }

    public function linkedItems(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'task_id')->with('linkedTask');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeCreatedByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // Accessors
    public function getCompletedSubtasksCountAttribute()
    {
        return $this->subtasks()->where('status', 'completed')->count();
    }

    public function getTotalTimeLoggedAttribute()
    {
        return $this->workLogs()->sum('hours');
    }
}
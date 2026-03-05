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
        'planned_start_date', // NEW: User-defined start date
        'tags',
        'completed_at',
        'started_at', // Auto-set when work actually begins
        'estimated_hours',
        'actual_hours',
        'sla_hours',
        'sla_breached',
        'actual_completion_time',
        'completed_on_time',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'planned_start_date' => 'datetime', // NEW
        'tags' => 'array',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'sla_breached' => 'boolean',
        'completed_on_time' => 'boolean',
    ];

    /**
     * Load business relationships by default
     */
    protected $with = ['assignedTo.employee', 'createdBy.employee'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            // If no planned_start_date is set, default to creation time
            if (!$task->planned_start_date) {
                $task->planned_start_date = now();
            }
            
            // If task is created with status 'in_progress' or 'completed', set started_at
            if (in_array($task->status, ['in_progress', 'under_review', 'completed']) && !$task->started_at) {
                $task->started_at = now();
            }
        });

        static::updating(function ($task) {
            if ($task->isDirty('status') && $task->status === 'completed') {
                $task->handleCompletion();
            }
            
            // Auto-set started_at when status changes to in_progress (but allow manual override)
            if ($task->isDirty('status') && $task->status === 'in_progress' && !$task->started_at) {
                $task->started_at = now();
            }
        });
    }

    /**
     * Handle task completion
     */
    protected function handleCompletion()
    {
        if (!$this->completed_at) {
            $this->completed_at = now();
            $this->calculateCompletionMetrics();
        }
    }

    /**
     * Calculate completion metrics
     */
    protected function calculateCompletionMetrics()
    {
        // Calculate actual time from START (use started_at if available, otherwise planned_start_date)
        $startTime = $this->started_at ?? $this->planned_start_date ?? $this->created_at;
        $completionTime = $startTime->diffInHours($this->completed_at, true);
        $this->actual_completion_time = round($completionTime, 2);

        // Check if completed on time (before deadline)
        if ($this->deadline) {
            $this->completed_on_time = $this->completed_at <= $this->deadline;
            
            // Check SLA breach
            if ($this->sla_hours) {
                $slaDeadline = $startTime->copy()->addHours($this->sla_hours);
                $this->sla_breached = $this->completed_at > $slaDeadline;
            } else {
                $this->sla_breached = !$this->completed_on_time;
            }
        }

        // Sum up work logs if available
        if ($this->workLogs()->exists()) {
            $this->actual_hours = $this->workLogs()->sum('hours');
        }
    }

    /**
     * Get the effective start date (started_at if available, otherwise planned_start_date)
     */
    public function getEffectiveStartDateAttribute()
    {
        return $this->started_at ?? $this->planned_start_date ?? $this->created_at;
    }

    /**
     * Check if task was started before it was planned
     */
    public function getStartedEarlyAttribute(): bool
    {
        if (!$this->started_at || !$this->planned_start_date) {
            return false;
        }
        
        return $this->started_at < $this->planned_start_date;
    }

    /**
     * Check if task was started late
     */
    public function getStartedLateAttribute(): bool
    {
        if (!$this->started_at || !$this->planned_start_date) {
            return false;
        }
        
        return $this->started_at > $this->planned_start_date;
    }

    /**
     * Get time remaining until deadline (in hours)
     */
    public function getTimeRemainingAttribute(): ?float
    {
        if ($this->completed_at || !$this->deadline) {
            return null;
        }

        $remaining = now()->diffInHours($this->deadline, false);
        return round($remaining, 2);
    }

    /**
     * Get SLA status
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->completed_at) {
            if ($this->sla_breached) {
                return 'breached';
            }
            return $this->completed_on_time ? 'met' : 'missed_deadline';
        }

        if (!$this->deadline) {
            return 'no_deadline';
        }

        $remaining = $this->time_remaining;
        
        if ($remaining === null) {
            return 'completed';
        }

        if ($remaining < 0) {
            return 'overdue';
        }

        $totalTime = $this->effective_start_date->diffInHours($this->deadline, true);
        $percentRemaining = ($remaining / $totalTime) * 100;

        if ($percentRemaining < 10) {
            return 'critical';
        }

        if ($percentRemaining < 25) {
            return 'warning';
        }

        return 'on_track';
    }

    /**
     * Check if task is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->completed_at || !$this->deadline) {
            return false;
        }

        return now() > $this->deadline;
    }

    /**
     * Get completion percentage based on subtasks
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalSubtasks = $this->subtasks()->count();
        
        if ($totalSubtasks === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $completedSubtasks = $this->subtasks()->where('status', 'completed')->count();
        return round(($completedSubtasks / $totalSubtasks) * 100);
    }

    /**
     * Scope: Overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')
            ->where('deadline', '<', now());
    }

    /**
     * Scope: Tasks at risk (less than 24 hours remaining)
     */
    public function scopeAtRisk($query)
    {
        return $query->whereNull('completed_at')
            ->where('deadline', '>', now())
            ->where('deadline', '<', now()->addHours(24));
    }

    /**
     * Scope: SLA breached tasks
     */
    public function scopeSlaBreached($query)
    {
        return $query->where('sla_breached', true);
    }

    /**
     * Scope: Completed on time
     */
    public function scopeCompletedOnTime($query)
    {
        return $query->whereNotNull('completed_at')
            ->where('completed_on_time', true);
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
    
    /**
     * Check if task is cross-business
     */
    public function getIsCrossBusinessAttribute(): bool
    {
        $assignedEmployee = $this->assignedTo->employee ?? null;
        $creatorEmployee = $this->createdBy->employee ?? null;
        
        if (!$assignedEmployee || !$creatorEmployee) {
            return false;
        }
        
        return $assignedEmployee->business_id !== $creatorEmployee->business_id;
    }
}
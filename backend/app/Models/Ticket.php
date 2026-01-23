<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'description',
        'user_id',
        'approver_id',
        'department_id',
        'category',
        'subcategory',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'comments',
        'resolution_notes',
        'attachments',
        'approved_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'approved_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'attachments' => 'array',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    protected $appends = [
        'status_label',
        'priority_label',
        'type_label',
        'is_overdue',
        'time_spent',
        'sla_status',
    ];

    protected $with = ['user', 'approver', 'department', 'assignedUsers'];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_REOPENED = 'reopened';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Type constants
    const TYPE_ISSUE = 'issue';
    const TYPE_REQUEST = 'request';
    const TYPE_CHANGE_REQUEST = 'change_request';

     /**
     * Add these relationships to the existing model
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function publicComments(): HasMany
    {
        return $this->hasMany(TicketComment::class)
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc');
    }

    public function internalComments(): HasMany
    {
        return $this->hasMany(TicketComment::class)
            ->where('is_internal', true)
            ->orderBy('created_at', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->orderBy('created_at', 'desc');
    }
    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select(['id', 'first_name', 'last_name', 'email']); // Removed avatar
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id')
            ->select(['id', 'first_name', 'last_name', 'email']); // Removed avatar
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)
            ->select(['id', 'name', 'code']);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_assignments')
            ->withTimestamps()
            ->withPivot(['role', 'assigned_at', 'completed_at'])
            ->select(['users.id', 'first_name', 'last_name', 'email']); // Removed avatar
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_REOPENED => 'Reopened',
            default => ucfirst($this->status),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => ucfirst($this->priority),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ISSUE => 'Issue',
            self::TYPE_REQUEST => 'Request',
            self::TYPE_CHANGE_REQUEST => 'Change Request',
            default => ucfirst($this->type),
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return false;
        }
        return $this->due_date->isPast();
    }

    public function getTimeSpentAttribute(): float
    {
        return $this->actual_hours ?? 0;
    }

    public function getSlaStatusAttribute(): string
    {
        if (!$this->due_date || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return 'completed';
        }

        $hoursRemaining = now()->diffInHours($this->due_date, false);
        
        if ($hoursRemaining > 24) {
            return 'on_track';
        } elseif ($hoursRemaining > 0) {
            return 'warning';
        } else {
            return 'breached';
        }
    }

    /**
     * Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIssues($query)
    {
        return $query->where('type', self::TYPE_ISSUE);
    }

    public function scopeRequests($query)
    {
        return $query->where('type', self::TYPE_REQUEST);
    }

    public function scopeChangeRequests($query)
    {
        return $query->where('type', self::TYPE_CHANGE_REQUEST);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->whereHas('assignedUsers', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeRequiringAttention($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->where('priority', '!=', self::PRIORITY_LOW);
    }

    /**
     * Helper methods
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_REJECTED]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReopened(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Get available next statuses based on current status and type
     */
    public function getAvailableStatuses(): array
    {
        $baseStatuses = [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_REOPENED => 'Reopened',
        ];

        // Different workflows based on type
        $workflows = [
            self::TYPE_ISSUE => [
                self::STATUS_DRAFT => ['pending', 'in_progress'],
                self::STATUS_PENDING => ['in_progress', 'on_hold'],
                self::STATUS_IN_PROGRESS => ['resolved', 'on_hold'],
                self::STATUS_ON_HOLD => ['in_progress'],
                self::STATUS_RESOLVED => ['closed', 'reopened'],
                self::STATUS_REOPENED => ['in_progress'],
            ],
            self::TYPE_REQUEST => [
                self::STATUS_DRAFT => ['pending'],
                self::STATUS_PENDING => ['approved', 'rejected'],
                self::STATUS_APPROVED => ['in_progress'],
                self::STATUS_IN_PROGRESS => ['resolved', 'on_hold'],
                self::STATUS_ON_HOLD => ['in_progress'],
                self::STATUS_RESOLVED => ['closed'],
                self::STATUS_REJECTED => ['closed', 'reopened'],
            ],
            self::TYPE_CHANGE_REQUEST => [
                self::STATUS_DRAFT => ['pending'],
                self::STATUS_PENDING => ['in_review'],
                self::STATUS_IN_REVIEW => ['approved', 'rejected'],
                self::STATUS_APPROVED => ['in_progress'],
                self::STATUS_IN_PROGRESS => ['resolved', 'on_hold'],
                self::STATUS_ON_HOLD => ['in_progress'],
                self::STATUS_RESOLVED => ['closed'],
                self::STATUS_REJECTED => ['closed', 'reopened'],
            ],
        ];

        $currentWorkflow = $workflows[$this->type][$this->status] ?? [];
        
        return array_filter($baseStatuses, function($key) use ($currentWorkflow) {
            return in_array($key, $currentWorkflow);
        }, ARRAY_FILTER_USE_KEY);
    }
}
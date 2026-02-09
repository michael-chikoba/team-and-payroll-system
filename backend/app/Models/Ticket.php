<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'first_response_at',
        'response_sla_hours',
        'resolution_sla_hours',
        'response_sla_breached',
        'resolution_sla_breached',
        'actual_response_time',
        'actual_resolution_time',
        'use_business_hours',
        'started_at',
        'business_id',
        'business_group_id',
        'is_group_ticket',
        'assigned_business_id',
        'response_time_hours',
        'resolution_time_hours',
        'meets_response_sla',
        'meets_resolution_sla',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'approved_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'attachments' => 'array',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'first_response_at' => 'datetime',
        'response_sla_breached' => 'boolean',
        'resolution_sla_breached' => 'boolean',
        'use_business_hours' => 'boolean',
        'started_at' => 'datetime',
        'meets_response_sla' => 'boolean',
        'meets_resolution_sla' => 'boolean',
        'response_time_hours' => 'integer',
        'resolution_time_hours' => 'integer',
        'is_group_ticket' => 'boolean',
        'response_sla_hours' => 'float',
        'resolution_sla_hours' => 'float',
        'actual_response_time' => 'float',
        'actual_resolution_time' => 'float',
    ];

    protected $appends = [
        'status_label',
        'priority_label',
        'type_label',
        'is_overdue',
        'time_spent',
    ];

    protected $with = ['user', 'approver', 'assignedUsers'];

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
     * Boot method to auto-track SLA metrics
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set SLA defaults when ticket is created
        static::creating(function ($ticket) {
            // Set SLA defaults if not already set
            if (!$ticket->response_sla_hours) {
                $ticket->response_sla_hours = $ticket->getDefaultResponseSLA();
            }
            
            if (!$ticket->resolution_sla_hours) {
                $ticket->resolution_sla_hours = $ticket->getDefaultResolutionSLA();
            }
        });

        // Track status changes
        static::updating(function ($ticket) {
            if ($ticket->isDirty('status')) {
                $newStatus = $ticket->status;

                // Track when work starts
                if ($newStatus === 'in_progress' && !$ticket->started_at) {
                    $ticket->started_at = now();
                }

                // Track resolution
                if (in_array($newStatus, ['resolved', 'closed']) && !$ticket->resolved_at) {
                    $ticket->resolved_at = now();
                    $ticket->calculateResolutionMetrics();
                }
            }
        });

        // After ticket is created, set meets_sla flags (opposite of breached flags)
        static::created(function ($ticket) {
            $ticket->meets_response_sla = !$ticket->response_sla_breached;
            $ticket->meets_resolution_sla = !$ticket->resolution_sla_breached;
            $ticket->saveQuietly();
        });
    }

    /**
     * Get default response SLA based on priority and type
     */
    public function getDefaultResponseSLA(): float
    {
        return (float) match($this->priority) {
            'critical' => 2,   // 2 hours
            'high' => 4,       // 4 hours
            'medium' => 24,    // 24 hours (1 day)
            'low' => 48,       // 48 hours (2 days)
            default => 24,
        };
    }

    /**
     * Get default resolution SLA based on priority and type
     */
    public function getDefaultResolutionSLA(): float
    {
        $baseSLA = (float) match($this->priority) {
            'critical' => 8,    // 8 hours
            'high' => 24,       // 24 hours (1 day)
            'medium' => 72,     // 72 hours (3 days)
            'low' => 120,       // 120 hours (5 days)
            default => 72,
        };

        // Multiply by type complexity
        $multiplier = match($this->type) {
            'issue' => 1.0,
            'request' => 1.5,
            'change_request' => 2.0,
            default => 1.0,
        };

        return $baseSLA * $multiplier;
    }

    /**
     * Record first response time
     */
    public function recordFirstResponse()
    {
        if (!$this->first_response_at) {
            $this->first_response_at = now();
            $this->calculateResponseMetrics();
            $this->save();
        }
    }

    /**
     * Calculate response time metrics
     */
    protected function calculateResponseMetrics()
    {
        if (!$this->first_response_at) {
            return;
        }

        $responseTime = $this->use_business_hours 
            ? $this->calculateBusinessHours($this->created_at, $this->first_response_at)
            : $this->created_at->diffInHours($this->first_response_at, true);

        $this->actual_response_time = (float) round($responseTime, 2);
        $this->response_time_hours = (int) round($responseTime);
        
        // Check if SLA was breached
        if ($this->response_sla_hours && $this->actual_response_time > $this->response_sla_hours) {
            $this->response_sla_breached = true;
            $this->meets_response_sla = false;
        } else {
            $this->response_sla_breached = false;
            $this->meets_response_sla = true;
        }
    }

    /**
     * Calculate resolution time metrics
     */
    protected function calculateResolutionMetrics()
    {
        if (!$this->resolved_at) {
            return;
        }

        $resolutionTime = $this->use_business_hours
            ? $this->calculateBusinessHours($this->created_at, $this->resolved_at)
            : $this->created_at->diffInHours($this->resolved_at, true);

        $this->actual_resolution_time = (float) round($resolutionTime, 2);
        $this->resolution_time_hours = (int) round($resolutionTime);
        
        // Check if SLA was breached
        if ($this->resolution_sla_hours && $this->actual_resolution_time > $this->resolution_sla_hours) {
            $this->resolution_sla_breached = true;
            $this->meets_resolution_sla = false;
        } else {
            $this->resolution_sla_breached = false;
            $this->meets_resolution_sla = true;
        }
    }

    /**
     * Calculate business hours between two timestamps
     * Business hours: Monday-Friday, 9 AM - 5 PM
     */
    protected function calculateBusinessHours(Carbon $start, Carbon $end): float
    {
        $businessHours = 0;
        $current = $start->copy();

        while ($current < $end) {
            // Skip weekends
            if ($current->isWeekend()) {
                $current->addDay()->setTime(9, 0);
                continue;
            }

            // Set to business hours
            $dayStart = $current->copy()->setTime(9, 0);
            $dayEnd = $current->copy()->setTime(17, 0);

            // Calculate hours for this day
            $periodStart = $current > $dayStart ? $current : $dayStart;
            $periodEnd = $end < $dayEnd ? $end : $dayEnd;

            if ($periodStart < $periodEnd) {
                $businessHours += $periodStart->diffInHours($periodEnd, true);
            }

            // Move to next day
            $current->addDay()->setTime(9, 0);
        }

        return $businessHours;
    }

    /**
     * Get response time remaining (in hours)
     */
    public function getResponseTimeRemainingAttribute(): ?float
    {
        if ($this->first_response_at || !$this->response_sla_hours) {
            return null;
        }

        // Ensure response_sla_hours is a float
        $slaHours = (float) $this->response_sla_hours;
        $deadline = $this->created_at->copy()->addHours($slaHours);
        $remaining = now()->diffInHours($deadline, false);
        
        return round($remaining, 2);
    }

    /**
     * Get resolution time remaining (in hours)
     */
    public function getResolutionTimeRemainingAttribute(): ?float
    {
        if ($this->resolved_at || !$this->resolution_sla_hours) {
            return null;
        }

        // Ensure resolution_sla_hours is a float
        $slaHours = (float) $this->resolution_sla_hours;
        $deadline = $this->created_at->copy()->addHours($slaHours);
        $remaining = now()->diffInHours($deadline, false);
        
        return round($remaining, 2);
    }

    /**
     * Get response SLA status
     */
    public function getResponseSlaStatusAttribute(): string
    {
        if ($this->first_response_at) {
            return $this->response_sla_breached ? 'breached' : 'met';
        }

        if (!$this->response_sla_hours) {
            return 'no_sla';
        }

        $remaining = $this->response_time_remaining;
        
        if ($remaining === null) {
            return 'met';
        }

        if ($remaining < 0) {
            return 'breached';
        }

        $slaHours = (float) $this->response_sla_hours;
        if ($remaining < ($slaHours * 0.25)) {
            return 'critical';
        }

        if ($remaining < ($slaHours * 0.5)) {
            return 'warning';
        }

        return 'on_track';
    }

    /**
     * Get resolution SLA status
     */
    public function getResolutionSlaStatusAttribute(): string
    {
        if ($this->resolved_at) {
            return $this->resolution_sla_breached ? 'breached' : 'met';
        }

        if (!$this->resolution_sla_hours) {
            return 'no_sla';
        }

        $remaining = $this->resolution_time_remaining;
        
        if ($remaining === null) {
            return 'met';
        }

        if ($remaining < 0) {
            return 'breached';
        }

        $slaHours = (float) $this->resolution_sla_hours;
        if ($remaining < ($slaHours * 0.25)) {
            return 'critical';
        }

        if ($remaining < ($slaHours * 0.5)) {
            return 'warning';
        }

        return 'on_track';
    }

    /**
     * Check if response is overdue
     */
    public function getIsResponseOverdueAttribute(): bool
    {
        if ($this->first_response_at) {
            return false;
        }

        $remaining = $this->response_time_remaining;
        return $remaining !== null && $remaining < 0;
    }

    /**
     * Check if resolution is overdue
     */
    public function getIsResolutionOverdueAttribute(): bool
    {
        if ($this->resolved_at) {
            return false;
        }

        $remaining = $this->resolution_time_remaining;
        return $remaining !== null && $remaining < 0;
    }

    /**
     * Relationships
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select(['id', 'first_name', 'last_name', 'email']);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id')
            ->select(['id', 'first_name', 'last_name', 'email']);
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
            ->select(['users.id', 'first_name', 'last_name', 'email']);
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
        return (float) ($this->actual_hours ?? 0);
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

    public function scopeResponseSlaBreached($query)
    {
        return $query->where('response_sla_breached', true);
    }

    public function scopeResolutionSlaBreached($query)
    {
        return $query->where('resolution_sla_breached', true);
    }

    public function scopeAtRiskOfBreach($query)
    {
        return $query->whereNull('resolved_at')
            ->where(function($q) {
                $q->whereRaw('TIMESTAMPDIFF(HOUR, created_at, NOW()) > (response_sla_hours * 0.75)')
                  ->orWhereRaw('TIMESTAMPDIFF(HOUR, created_at, NOW()) > (resolution_sla_hours * 0.75)');
            });
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
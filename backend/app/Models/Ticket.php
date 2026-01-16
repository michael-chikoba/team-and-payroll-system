<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'approver_id',
        'status',
        'priority',
        'due_date',
        'comments',
        'approved_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'priority_label',
    ];

    /**
     * Always load these relationships
     */
    protected $with = ['user', 'approver'];

    /**
     * Relationships
     */
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

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'in_progress' => 'In Progress',
            default => ucfirst($this->status),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['approved', 'rejected']);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->whereHas('user.employee', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        });
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isApproved();
    }

    public function canBeEdited(): bool
    {
        return $this->isPending();
    }

    public function canBeDeleted(): bool
    {
        return $this->isPending() || $this->isRejected();
    }
}
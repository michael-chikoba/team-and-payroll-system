<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessGroupMembership extends Model
{
    protected $fillable = [
        'business_group_id',
        'business_id',
        'role',
        'is_primary',
        'joined_at',
        'can_manage_group',
        'can_invite_businesses',
        'can_view_all_tickets',
        'can_assign_cross_business_tasks',
        'status',
        'invited_by_user_id',
        'approved_at',
        'approved_by_user_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_manage_group' => 'boolean',
        'can_invite_businesses' => 'boolean',
        'can_view_all_tickets' => 'boolean',
        'can_assign_cross_business_tasks' => 'boolean',
        'joined_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
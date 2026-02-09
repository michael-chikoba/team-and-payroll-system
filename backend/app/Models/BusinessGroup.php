<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'group_type',
        'parent_group_id',
        'allow_cross_business_tickets',
        'allow_cross_business_tasks',
        'allow_cross_business_projects',
        'allow_employee_visibility',
        'allow_resource_sharing',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'allow_cross_business_tickets' => 'boolean',
        'allow_cross_business_tasks' => 'boolean',
        'allow_cross_business_projects' => 'boolean',
        'allow_employee_visibility' => 'boolean',
        'allow_resource_sharing' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_group_memberships')
            ->withPivot([
                'role',
                'is_primary',
                'can_manage_group',
                'can_invite_businesses',
                'can_view_all_tickets',
                'can_assign_cross_business_tasks',
                'status',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function activeBusinesses(): BelongsToMany
    {
        return $this->businesses()->wherePivot('status', 'active');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(BusinessGroupMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    public function parentGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'parent_group_id');
    }

    public function childGroups(): HasMany
    {
        return $this->hasMany(BusinessGroup::class, 'parent_group_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessGroupInvitation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(BusinessGroupActivityLog::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('group_type', $type);
    }

    // ==================== HELPER METHODS ====================

    public function isBusinessMember(int $businessId): bool
    {
        return $this->businesses()
            ->where('businesses.id', $businessId)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function canBusinessManageGroup(int $businessId): bool
    {
        return $this->businesses()
            ->where('businesses.id', $businessId)
            ->wherePivot('can_manage_group', true)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function canBusinessInviteOthers(int $businessId): bool
    {
        return $this->businesses()
            ->where('businesses.id', $businessId)
            ->wherePivot('can_invite_businesses', true)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function getAllEmployees()
    {
        if (!$this->allow_employee_visibility) {
            return collect();
        }

        $businessIds = $this->activeBusinesses()->pluck('businesses.id');
        
        return Employee::whereIn('business_id', $businessIds)
            ->with('user')
            ->get();
    }

    public function getAllUsers()
    {
        $businessIds = $this->activeBusinesses()->pluck('businesses.id');
        
        return User::whereHas('employee', function ($query) use ($businessIds) {
            $query->whereIn('business_id', $businessIds);
        })->get();
    }

    public function getBusinesses()
    {
        return $this->activeBusinesses;
    }

    public function getMemberCount(): int
    {
        return $this->activeBusinesses()->count();
    }

    public function logActivity(string $action, string $description, ?int $businessId = null, ?int $userId = null, array $metadata = []): void
    {
        $this->activityLogs()->create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
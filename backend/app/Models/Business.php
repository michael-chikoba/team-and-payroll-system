<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        // Original fields
        'name',
        'legal_name',
        'registration_number',
        'tax_identification_number',
        'business_type',
        'industry',
        'website',
        'logo_path',
        'email',
        'phone',
        'fax',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_id',
        'currency_code',
        'fiscal_year_start',
        'pay_period',
        'status',
        'is_verified',
        
        // SuperAdmin fields - Subscription & Limits
        'employee_limit',
        'current_employee_count',
        'subscription_tier',
        'subscription_start_date',
        'subscription_end_date',
        'is_trial',
        'trial_end_date',
        
        // SuperAdmin fields - System Management
        'features',
        'restrictions',
        'admin_notes',
        
        // SuperAdmin fields - Audit
        'created_by_admin_id',
        'last_active_at',
        'suspended_at',
        'suspended_by_admin_id',
        'suspension_reason',
        
        // Business Group fields
        'primary_business_group_id',
        'allow_group_collaboration',
    ];

    protected $casts = [
        'fiscal_year_start' => 'date',
        'is_verified' => 'boolean',
        'allow_group_collaboration' => 'boolean',
        
        // SuperAdmin casts
        'is_trial' => 'boolean',
        'features' => 'array',
        'restrictions' => 'array',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'trial_end_date' => 'date',
        'last_active_at' => 'datetime',
        'suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'full_address',
        'formatted_business_type',
        'is_active',
        'is_subscription_active',
        'is_at_employee_limit',
        'employee_usage_percentage',
    ];

    /**
     * ========================================
     * CORE RELATIONSHIPS
     * ========================================
     */

    /**
     * Get the country that the business belongs to
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all admins for the business (through business_admins pivot table)
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_admins')
                    ->withPivot('role', 'is_primary')
                    ->withTimestamps();
    }

    /**
     * Get the primary admin for the business
     */
    public function primaryAdmin()
    {
        return $this->admins()
                    ->wherePivot('is_primary', true)
                    ->withPivot('role', 'is_primary');
    }

    /**
     * Get all employees for the business
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all payrolls for the business
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * ========================================
     * SUPERADMIN RELATIONSHIPS
     * ========================================
     */

    /**
     * Get the admin who created this business (SuperAdmin)
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Get the admin who suspended this business (SuperAdmin)
     */
    public function suspendedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_admin_id');
    }

    /**
     * Get all activity logs for this business (SuperAdmin)
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(BusinessActivityLog::class);
    }

    /**
     * Get all employee limit history for this business (SuperAdmin)
     */
    public function limitHistory(): HasMany
    {
        return $this->hasMany(BusinessLimitHistory::class);
    }

    /**
     * ========================================
     * BUSINESS GROUP RELATIONSHIPS
     * ========================================
     */

    /**
     * Get all business groups this business belongs to
     */
    public function businessGroups(): BelongsToMany
    {
        return $this->belongsToMany(BusinessGroup::class, 'business_group_memberships')
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

    /**
     * Get only active business groups
     */
    public function activeBusinessGroups(): BelongsToMany
    {
        return $this->businessGroups()->wherePivot('status', 'active');
    }

    /**
     * Get the primary business group
     */
    public function primaryBusinessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'primary_business_group_id');
    }

    /**
     * Get all group memberships
     */
    public function groupMemberships(): HasMany
    {
        return $this->hasMany(BusinessGroupMembership::class);
    }

    /**
     * Get active group memberships
     */
    public function activeGroupMemberships(): HasMany
    {
        return $this->groupMemberships()->where('status', 'active');
    }

    /**
     * Get received group invitations
     */
    public function receivedGroupInvitations(): HasMany
    {
        return $this->hasMany(BusinessGroupInvitation::class, 'invited_business_id');
    }

    /**
     * Get pending group invitations
     */
    public function pendingGroupInvitations(): HasMany
    {
        return $this->receivedGroupInvitations()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * ========================================
     * SCOPES
     * ========================================
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('suspended_at');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to filter businesses by country
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to search businesses by name or legal name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
    }

    /**
     * Scope for suspended businesses (SuperAdmin)
     */
    public function scopeSuspended($query)
    {
        return $query->whereNotNull('suspended_at');
    }

    /**
     * Scope for businesses on trial (SuperAdmin)
     */
    public function scopeOnTrial($query)
    {
        return $query->where('is_trial', true)
                    ->where('trial_end_date', '>=', now());
    }

    /**
     * Scope for businesses with active subscription (SuperAdmin)
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('is_trial', true)
                    ->where('trial_end_date', '>=', now());
            })->orWhere(function ($subQ) {
                $subQ->where('is_trial', false)
                    ->where('subscription_end_date', '>=', now());
            });
        });
    }

    /**
     * Scope for businesses at employee limit (SuperAdmin)
     */
    public function scopeAtLimit($query)
    {
        return $query->whereColumn('current_employee_count', '>=', 'employee_limit');
    }

    /**
     * Scope for businesses by subscription tier (SuperAdmin)
     */
    public function scopeByTier($query, $tier)
    {
        return $query->where('subscription_tier', $tier);
    }

    /**
     * ========================================
     * ACCESSORS
     * ========================================
     */

    /**
     * Get the full address of the business
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->postal_code;
        
        if ($this->country) {
            $address .= ', ' . $this->country->name;
        }
        
        return $address;
    }

    /**
     * Get formatted business type
     */
    public function getFormattedBusinessTypeAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->business_type));
    }

    /**
     * Check if the business is active (SuperAdmin)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && !$this->suspended_at;
    }

    /**
     * Check if the subscription is active (SuperAdmin)
     */
    public function getIsSubscriptionActiveAttribute(): bool
    {
        if ($this->is_trial) {
            return $this->trial_end_date && $this->trial_end_date->isFuture();
        }

        return $this->subscription_end_date && $this->subscription_end_date->isFuture();
    }

    /**
     * Check if the business is at employee limit (SuperAdmin)
     */
    public function getIsAtEmployeeLimitAttribute(): bool
    {
        return $this->current_employee_count >= $this->employee_limit;
    }

    /**
     * Get employee usage percentage (SuperAdmin)
     */
    public function getEmployeeUsagePercentageAttribute(): float
    {
        if ($this->employee_limit === 0) {
            return 0;
        }

        return round(($this->current_employee_count / $this->employee_limit) * 100, 2);
    }

    /**
     * ========================================
     * BUSINESS ADMIN METHODS
     * ========================================
     */

    /**
     * Make a user the primary admin of the business
     */
    public function makePrimaryAdmin(User $user): void
    {
        // Remove existing primary admin
        $this->admins()->wherePivot('is_primary', true)->update(['is_primary' => false]);
        
        // Check if user is already an admin
        if ($this->admins()->where('user_id', $user->id)->exists()) {
            // Update existing admin to primary
            $this->admins()->updateExistingPivot($user->id, [
                'is_primary' => true,
                'role' => 'owner',
                'updated_at' => now()
            ]);
        } else {
            // Add new admin as primary
            $this->admins()->attach($user->id, [
                'role' => 'owner',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Add an admin to the business
     */
    public function addAdmin(User $user, string $role = 'admin', bool $isPrimary = false): void
    {
        if ($isPrimary) {
            $this->makePrimaryAdmin($user);
            return;
        }

        if (!$this->admins()->where('user_id', $user->id)->exists()) {
            $this->admins()->attach($user->id, [
                'role' => $role,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Remove an admin from the business
     */
    public function removeAdmin(User $user): bool
    {
        $admin = $this->admins()->where('user_id', $user->id)->first();
        
        // Prevent removing the only primary admin
        if ($admin && $admin->pivot->is_primary && $this->admins()->wherePivot('is_primary', true)->count() <= 1) {
            return false;
        }

        $this->admins()->detach($user->id);
        return true;
    }

    /**
     * Check if a user is an admin of the business
     */
    public function hasAdmin(User $user): bool
    {
        return $this->admins()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the primary admin of the business
     */
    public function isPrimaryAdmin(User $user): bool
    {
        return $this->admins()
                    ->where('user_id', $user->id)
                    ->wherePivot('is_primary', true)
                    ->exists();
    }

    /**
     * ========================================
     * BUSINESS STATISTICS & INFO
     * ========================================
     */

    /**
     * Get business statistics
     */
    public function getStats(): array
    {
        return [
            'total_employees' => $this->employees()->count(),
            'active_employees' => $this->employees()->where('is_active', true)->count(),
            'total_admins' => $this->admins()->count(),
            'departments' => $this->employees()->distinct('department')->pluck('department'),
            'total_payrolls' => $this->payrolls()->count(),
        ];
    }

    /**
     * Check if business can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->employees()->count() === 0 && 
               $this->payrolls()->count() === 0;
    }

    /**
     * Get default settings for the business
     */
    public function getDefaultSettings(): array
    {
        return [
            'currency' => $this->currency_code,
            'pay_period' => $this->pay_period,
            'country_id' => $this->country_id,
            'fiscal_year_start' => $this->fiscal_year_start,
        ];
    }
// In App\Models\Business.php

public function canAssignCrossBusinessTasks($businessGroupId = null)
{
    if ($businessGroupId) {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $businessGroupId)
            ->wherePivot('can_assign_tasks', true)
            ->exists();
    }
    
    return $this->activeBusinessGroups()
        ->wherePivot('can_assign_tasks', true)
        ->exists();
}

public function canViewGroupTickets($businessGroupId = null)
{
    if ($businessGroupId) {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $businessGroupId)
            ->wherePivot('can_view_all_tickets', true)
            ->exists();
    }
    
    return $this->activeBusinessGroups()
        ->wherePivot('can_view_all_tickets', true)
        ->exists();
}
    /**
     * ========================================
     * SUPERADMIN METHODS
     * ========================================
     */

    /**
     * Update employee count (SuperAdmin)
     */
    public function updateEmployeeCount(): void
    {
        $this->current_employee_count = $this->employees()->where('is_active', true)->count();
        $this->saveQuietly(); // Save without firing events
    }

    /**
     * Check if business can add more employees (SuperAdmin)
     */
    public function canAddEmployees(int $count = 1): bool
    {
        return ($this->current_employee_count + $count) <= $this->employee_limit;
    }

    /**
     * Update last active timestamp (SuperAdmin)
     */
    public function touchLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Suspend the business (SuperAdmin)
     */
    public function suspend(int $adminId, string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_by_admin_id' => $adminId,
            'suspension_reason' => $reason,
        ]);

        // Log the suspension
        BusinessActivityLog::logActivity(
            $this->id,
            $adminId,
            'suspended',
            "Business suspended. Reason: {$reason}",
            ['status' => 'active'],
            ['status' => 'suspended', 'reason' => $reason]
        );
    }

    /**
     * Activate the business (SuperAdmin)
     */
    public function activate(int $adminId): void
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspended_by_admin_id' => null,
            'suspension_reason' => null,
        ]);

        // Log the activation
        BusinessActivityLog::logActivity(
            $this->id,
            $adminId,
            'activated',
            'Business activated',
            ['status' => 'suspended'],
            ['status' => 'active']
        );
    }

    /**
     * Update employee limit (SuperAdmin)
     */
    public function updateEmployeeLimit(int $newLimit, int $adminId, ?string $reason = null): void
    {
        $oldLimit = $this->employee_limit;

        // Record the limit change in history
        BusinessLimitHistory::recordChange(
            $this->id,
            $adminId,
            $oldLimit,
            $newLimit,
            $reason
        );

        // Update the limit
        $this->update(['employee_limit' => $newLimit]);

        // Log the activity
        BusinessActivityLog::logActivity(
            $this->id,
            $adminId,
            'limit_changed',
            "Employee limit changed from {$oldLimit} to {$newLimit}",
            ['employee_limit' => $oldLimit],
            ['employee_limit' => $newLimit, 'reason' => $reason]
        );
    }

    /**
     * Check if feature is enabled (SuperAdmin)
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->features) {
            return false;
        }

        return in_array($feature, $this->features);
    }

    /**
     * Enable a feature (SuperAdmin)
     */
    public function enableFeature(string $feature): void
    {
        $features = $this->features ?? [];
        
        if (!in_array($feature, $features)) {
            $features[] = $feature;
            $this->update(['features' => $features]);
        }
    }

    /**
     * Disable a feature (SuperAdmin)
     */
    public function disableFeature(string $feature): void
    {
        $features = $this->features ?? [];
        
        $features = array_filter($features, function($f) use ($feature) {
            return $f !== $feature;
        });
        
        $this->update(['features' => array_values($features)]);
    }

    /**
     * Check if restriction is active (SuperAdmin)
     */
    public function hasRestriction(string $restriction): bool
    {
        if (!$this->restrictions) {
            return false;
        }

        return in_array($restriction, $this->restrictions);
    }

    /**
     * Add a restriction (SuperAdmin)
     */
    public function addRestriction(string $restriction): void
    {
        $restrictions = $this->restrictions ?? [];
        
        if (!in_array($restriction, $restrictions)) {
            $restrictions[] = $restriction;
            $this->update(['restrictions' => $restrictions]);
        }
    }

    /**
     * Remove a restriction (SuperAdmin)
     */
    public function removeRestriction(string $restriction): void
    {
        $restrictions = $this->restrictions ?? [];
        
        $restrictions = array_filter($restrictions, function($r) use ($restriction) {
            return $r !== $restriction;
        });
        
        $this->update(['restrictions' => array_values($restrictions)]);
    }

    /**
     * Get subscription status info (SuperAdmin)
     */
    public function getSubscriptionInfo(): array
    {
        $isActive = $this->is_subscription_active;
        $expiresAt = $this->is_trial ? $this->trial_end_date : $this->subscription_end_date;
        $daysRemaining = $expiresAt ? now()->diffInDays($expiresAt, false) : 0;

        return [
            'is_active' => $isActive,
            'is_trial' => $this->is_trial,
            'tier' => $this->subscription_tier,
            'expires_at' => $expiresAt,
            'days_remaining' => max(0, $daysRemaining),
            'is_expired' => !$isActive,
        ];
    }

    /**
     * Extend subscription (SuperAdmin)
     */
    public function extendSubscription(int $months, int $adminId): void
    {
        $oldDate = $this->is_trial ? $this->trial_end_date : $this->subscription_end_date;
        $newDate = ($oldDate && $oldDate->isFuture() ? $oldDate : now())->addMonths($months);

        if ($this->is_trial) {
            $this->update(['trial_end_date' => $newDate]);
        } else {
            $this->update(['subscription_end_date' => $newDate]);
        }

        BusinessActivityLog::logActivity(
            $this->id,
            $adminId,
            'subscription_extended',
            "Subscription extended by {$months} months",
            ['expires_at' => $oldDate],
            ['expires_at' => $newDate]
        );
    }

    /**
     * Convert trial to paid subscription (SuperAdmin)
     */
    public function convertToPaid(string $tier, int $months, int $adminId): void
    {
        $this->update([
            'is_trial' => false,
            'trial_end_date' => null,
            'subscription_tier' => $tier,
            'subscription_start_date' => now(),
            'subscription_end_date' => now()->addMonths($months),
        ]);

        BusinessActivityLog::logActivity(
            $this->id,
            $adminId,
            'trial_converted',
            "Trial converted to {$tier} subscription for {$months} months",
            ['is_trial' => true],
            ['is_trial' => false, 'tier' => $tier]
        );
    }

    /**
     * Get employee limit remaining (SuperAdmin)
     */
    public function getRemainingEmployeeSlots(): int
    {
        return max(0, $this->employee_limit - $this->current_employee_count);
    }

    /**
     * Check if business needs attention (SuperAdmin)
     */
    public function needsAttention(): array
    {
        $issues = [];

        // Check if at employee limit
        if ($this->is_at_employee_limit) {
            $issues[] = 'at_employee_limit';
        }

        // Check if subscription expiring soon (within 7 days)
        $subscriptionInfo = $this->getSubscriptionInfo();
        if ($subscriptionInfo['is_active'] && $subscriptionInfo['days_remaining'] <= 7) {
            $issues[] = 'subscription_expiring_soon';
        }

        // Check if subscription expired
        if (!$subscriptionInfo['is_active']) {
            $issues[] = 'subscription_expired';
        }

        // Check if suspended
        if ($this->suspended_at) {
            $issues[] = 'suspended';
        }

        return $issues;
    }

    /**
     * ========================================
     * BUSINESS GROUP METHODS
     * ========================================
     */

    /**
     * Check if business is in a specific group
     */
    public function isInGroup(int $groupId): bool
    {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $groupId)
            ->exists();
    }

    /**
     * Get all businesses in the same group(s)
     */
    public function getGroupBusinesses(int $groupId)
    {
        $group = BusinessGroup::find($groupId);
        
        if (!$group || !$this->isInGroup($groupId)) {
            return collect();
        }

        return $group->activeBusinesses()
            ->where('businesses.id', '!=', $this->id)
            ->get();
    }

    /**
     * Get all employees across all business groups
     */
    public function getGroupEmployees()
    {
        $groupIds = $this->activeBusinessGroups()->pluck('business_groups.id');
        
        $employees = collect();
        
        foreach ($groupIds as $groupId) {
            $group = BusinessGroup::find($groupId);
            if ($group && $group->allow_employee_visibility) {
                $employees = $employees->merge($group->getAllEmployees());
            }
        }
        
        return $employees->unique('id');
    }

    /**
     * Check if business can access group tickets
     */
    public function canAccessGroupTickets(int $groupId): bool
    {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $groupId)
            ->wherePivot('can_view_all_tickets', true)
            ->exists();
    }

   

    /**
     * Check if business can manage a group
     */
    public function canManageGroup(int $groupId): bool
    {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $groupId)
            ->wherePivot('can_manage_group', true)
            ->exists();
    }

    /**
     * Check if business can invite others to a group
     */
    public function canInviteToGroup(int $groupId): bool
    {
        return $this->activeBusinessGroups()
            ->where('business_groups.id', $groupId)
            ->wherePivot('can_invite_businesses', true)
            ->exists();
    }

    /**
     * Get business role in a group
     */
    public function getGroupRole(int $groupId): ?string
    {
        $membership = $this->activeBusinessGroups()
            ->where('business_groups.id', $groupId)
            ->first();
        
        return $membership ? $membership->pivot->role : null;
    }

    /**
     * Join a business group
     */
    public function joinGroup(int $groupId, string $role = 'member', ?int $invitedBy = null): bool
    {
        if ($this->isInGroup($groupId)) {
            return false;
        }

        $this->businessGroups()->attach($groupId, [
            'role' => $role,
            'is_primary' => $this->activeBusinessGroups()->count() === 0,
            'status' => 'active',
            'joined_at' => now(),
            'invited_by_user_id' => $invitedBy,
        ]);

        return true;
    }

    /**
     * Leave a business group
     */
    public function leaveGroup(int $groupId): bool
    {
        if (!$this->isInGroup($groupId)) {
            return false;
        }

        $this->businessGroups()->detach($groupId);

        // If this was the primary group, clear it
        if ($this->primary_business_group_id == $groupId) {
            $this->update(['primary_business_group_id' => null]);
        }

        return true;
    }

    /**
     * Set primary business group
     */
    public function setPrimaryGroup(int $groupId): bool
    {
        if (!$this->isInGroup($groupId)) {
            return false;
        }

        // Update pivot to mark as primary
        $this->businessGroups()->updateExistingPivot($groupId, [
            'is_primary' => true
        ]);

        // Remove primary flag from other groups
        $this->businessGroups()
            ->where('business_groups.id', '!=', $groupId)
            ->updateExistingPivot('*', ['is_primary' => false]);

        // Update the primary_business_group_id
        $this->update(['primary_business_group_id' => $groupId]);

        return true;
    }

    /**
     * Get all group invitations count
     */
    public function getPendingGroupInvitationsCount(): int
    {
        return $this->pendingGroupInvitations()->count();
    }

    /**
     * Check if business has group collaboration enabled
     */
    public function hasGroupCollaborationEnabled(): bool
    {
        return $this->allow_group_collaboration === true;
    }

    /**
     * Enable group collaboration
     */
    public function enableGroupCollaboration(): void
    {
        $this->update(['allow_group_collaboration' => true]);
    }

    /**
     * Disable group collaboration
     */
    public function disableGroupCollaboration(): void
    {
        $this->update(['allow_group_collaboration' => false]);
    }
    
}
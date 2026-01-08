<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'type',
        'department_id',
        'created_by',
        'avatar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the business that owns the chat group.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the department if this is a department group.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all members of the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members')
            ->withPivot(['role', 'last_read_at', 'is_muted', 'muted_until'])
            ->withTimestamps();
    }

    /**
     * Get member records (through relationship).
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class);
    }

    /**
     * Get all messages in the group.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the latest message.
     */
    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }

    /**
     * Get the last message relationship.
     */
    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }

    /**
     * Scope: Get groups for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('members', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope: Get only active groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get groups by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get groups for a business.
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Check if user is a member.
     */
    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Add a member to the group.
     */
    public function addMember(int $userId, string $role = 'member'): void
    {
        if (!$this->isMember($userId)) {
            $this->members()->attach($userId, [
                'role' => $role,
                'last_read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(int $userId): bool
    {
        if ($this->isMember($userId)) {
            $this->members()->detach($userId);
            return true;
        }
        return false;
    }

    /**
     * Update member role.
     */
    public function updateMemberRole(int $userId, string $role): bool
    {
        if ($this->isMember($userId)) {
            $this->members()->updateExistingPivot($userId, [
                'role' => $role,
                'updated_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mark messages as read for a user.
     */
    public function markAsRead(int $userId): void
    {
        $this->memberships()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Get unread count for a specific user.
     */
    public function getUnreadCountForUser(int $userId): int
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();
        
        if (!$membership) {
            return 0;
        }

        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('created_at', '>', $membership->last_read_at ?? $this->created_at)
            ->count();
    }

    /**
     * Get member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Toggle mute for a user.
     */
    public function toggleMute(int $userId): bool
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();
        
        if ($membership) {
            $membership->update([
                'is_muted' => !$membership->is_muted
            ]);
            return $membership->is_muted;
        }
        
        return false;
    }

    /**
     * Check if group is muted for user.
     */
    public function isMutedForUser(int $userId): bool
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();
        return $membership ? $membership->is_muted : false;
    }

    /**
     * Get member role for user.
     */
    public function getMemberRole(int $userId): ?string
    {
        $member = $this->members()->where('user_id', $userId)->first();
        return $member ? $member->pivot->role : null;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // When creating a group, set is_active to true by default
        static::creating(function ($group) {
            if (!isset($group->is_active)) {
                $group->is_active = true;
            }
        });

        // Update last_message_id when a new message is created
        static::saved(function ($group) {
            $lastMessage = $group->messages()->latest()->first();
            if ($lastMessage && $group->last_message_id !== $lastMessage->id) {
                $group->withoutEvents(function () use ($group, $lastMessage) {
                    $group->update(['last_message_id' => $lastMessage->id]);
                });
            }
        });
    }
}
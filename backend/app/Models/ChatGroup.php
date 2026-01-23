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
        'is_channel',
        'is_private',
        'department_id',
        'created_by',
        'avatar',
        'is_active',
        'is_archived',
        'archived_at',
        'last_message_id',
        'channel_prefix',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_channel' => 'boolean',
        'is_private' => 'boolean',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'settings' => 'array',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members')
            ->withPivot([
                'role',
                'last_read_at',
                'is_muted',
                'muted_until',
                'joined_at',
                'invited_by',
                'notification_preferences',
                'is_favorite'
            ])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ChatFile::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ChatInvitation::class);
    }

    public function pinnedMessages(): HasMany
    {
        return $this->messages()->where('is_pinned', true)->orderByDesc('pinned_at');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('members', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    public function scopeChannels(Builder $query): Builder
    {
        return $query->where('is_channel', true);
    }

    public function scopeDirectMessages(Builder $query): Builder
    {
        return $query->where('type', 'direct');
    }

    public function scopePublicChannels(Builder $query): Builder
    {
        return $query->where('is_channel', true)->where('is_private', false);
    }

    public function scopePrivateChannels(Builder $query): Builder
    {
        return $query->where('is_channel', true)->where('is_private', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeSearchByName(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    // Helper Methods
    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function isChannel(): bool
    {
        return (bool) $this->is_channel;
    }

    public function isDirectMessage(): bool
    {
        return $this->type === 'direct';
    }

    public function isPrivate(): bool
    {
        return $this->is_private;
    }

    public function addMember(int $userId, string $role = 'member', ?int $invitedBy = null): void
    {
        if (!$this->isMember($userId)) {
            $this->members()->attach($userId, [
                'role' => $role,
                'last_read_at' => now(),
                'joined_at' => now(),
                'invited_by' => $invitedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create system message
            $inviter = $invitedBy ? User::find($invitedBy) : null;
            $addedUser = User::find($userId);

            $groupType = $this->isDirectMessage() ? 'conversation' : ($this->isChannel() ? 'channel' : 'group');

            ChatMessage::create([
                'chat_group_id' => $this->id,
                'user_id' => $invitedBy ?? $userId,
                'message' => $inviter
                    ? "{$inviter->name} added {$addedUser->name} to the {$groupType}"
                    : "{$addedUser->name} joined the {$groupType}",
                'type' => 'system',
            ]);
        }
    }

    public function removeMember(int $userId, ?int $removedBy = null): bool
    {
        if ($this->isMember($userId)) {
            $this->members()->detach($userId);

            // Create system message
            $remover = $removedBy ? User::find($removedBy) : null;
            $removedUser = User::find($userId);

            $groupType = $this->isDirectMessage() ? 'conversation' : ($this->isChannel() ? 'channel' : 'group');

            ChatMessage::create([
                'chat_group_id' => $this->id,
                'user_id' => $removedBy ?? $userId,
                'message' => $userId === $removedBy || !$removedBy
                    ? "{$removedUser->name} left the {$groupType}"
                    : "{$remover->name} removed {$removedUser->name} from the {$groupType}",
                'type' => 'system',
            ]);

            return true;
        }
        return false;
    }

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

    public function markAsRead(int $userId): void
    {
        $this->memberships()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    public function getUnreadCountForUser(int $userId): int
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();

        if (!$membership) {
            return 0;
        }
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('created_at', '>', $membership->last_read_at ?? $this->created_at)
            ->whereNull('thread_id') // Only count main messages, not thread replies
            ->count();
    }

    public function archive(?int $archivedBy = null): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
        if ($archivedBy) {
            $archiver = User::find($archivedBy);
            $groupType = $this->isChannel() ? 'channel' : 'group';
            ChatMessage::create([
                'chat_group_id' => $this->id,
                'user_id' => $archivedBy,
                'message' => "{$archiver->name} archived this {$groupType}",
                'type' => 'system',
            ]);
        }
    }

    public function unarchive(?int $unarchivedBy = null): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
        if ($unarchivedBy) {
            $unarchiver = User::find($unarchivedBy);
            $groupType = $this->isChannel() ? 'channel' : 'group';
            ChatMessage::create([
                'chat_group_id' => $this->id,
                'user_id' => $unarchivedBy,
                'message' => "{$unarchiver->name} unarchived this {$groupType}",
                'type' => 'system',
            ]);
        }
    }

    public function toggleFavorite(int $userId): bool
    {
        $membership = $this->memberships()->where('user_id', $userId)->first();

        if ($membership) {
            $newStatus = !$membership->is_favorite;
            $membership->update(['is_favorite' => $newStatus]);
            return $newStatus;
        }

        return false;
    }

    public function getDisplayName(): string
    {
        if ($this->isChannel()) {
            return ($this->is_private ? '🔒 ' : '#') . $this->name;
        }
        return $this->name;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (!isset($group->is_active)) {
                $group->is_active = true;
            }

            if (!isset($group->is_channel)) {
                $group->is_channel = in_array($group->type, ['channel', 'department']);
            }

            // Set channel prefix for channels
            if ($group->is_channel && !$group->channel_prefix) {
                $group->channel_prefix = $group->is_private ? '🔒' : '#';
            }
        });
    }
}
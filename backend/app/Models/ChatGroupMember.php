<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_group_id',
        'user_id',
        'role',
        'last_read_at',
        'is_muted',
        'muted_until',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'muted_until' => 'datetime',
        'is_muted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the chat group.
     */
    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if currently muted.
     */
    public function isMuted(): bool
    {
        if (!$this->is_muted) {
            return false;
        }

        if ($this->muted_until && $this->muted_until->isPast()) {
            $this->update(['is_muted' => false, 'muted_until' => null]);
            return false;
        }

        return true;
    }

    /**
     * Mute notifications.
     */
    public function mute(?int $hours = null): void
    {
        $this->update([
            'is_muted' => true,
            'muted_until' => $hours ? now()->addHours($hours) : null,
        ]);
    }

    /**
     * Unmute notifications.
     */
    public function unmute(): void
    {
        $this->update([
            'is_muted' => false,
            'muted_until' => null,
        ]);
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is member.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Promote to admin.
     */
    public function promoteToAdmin(): void
    {
        $this->update(['role' => 'admin']);
    }

    /**
     * Demote to member.
     */
    public function demoteToMember(): void
    {
        $this->update(['role' => 'member']);
    }
}
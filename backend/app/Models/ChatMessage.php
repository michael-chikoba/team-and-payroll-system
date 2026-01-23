<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_group_id',
        'user_id',
        'message',
        'type',
        'reply_to_message_id',
        'thread_id',
        'is_thread_reply',
        'mentions',
        'attachment_url',
        'attachment_name',
        'attachment_size',
        'is_edited',
        'edited_at',
        'is_deleted',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $casts = [
        'mentions' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'is_thread_reply' => 'boolean',
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
        'pinned_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isFromIntegration(): bool
{
    return $this->type === 'integration';
}

public function getIntegrationName(): ?string
{
    return $this->metadata['integration_name'] ?? null;
}

public function getIntegrationIcon(): ?string
{
    return $this->metadata['icon_url'] ?? null;
}

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_message_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatReaction::class);
    }

    public function pinnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(ChatMention::class);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function getFormattedTimeAttribute(): string
    {
        $now = now();
        $messageTime = $this->created_at;

        if ($messageTime->isToday()) {
            return $messageTime->format('g:i A');
        } elseif ($messageTime->isYesterday()) {
            return 'Yesterday ' . $messageTime->format('g:i A');
        } else {
            return $messageTime->format('M j, Y g:i A');
        }
    }
  




    /**
     * Get replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'reply_to_message_id');
    }

 

    /**
     * Scope: Get messages by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get messages after a specific date.
     */
    public function scopeAfter(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '>', $date);
    }

    /**
     * Scope: Get messages before a specific date.
     */
    public function scopeBefore(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '<', $date);
    }

    /**
     * Check if user can edit this message.
     */
    public function canEdit(int $userId): bool
    {
        return $this->user_id === $userId && 
               $this->created_at->diffInMinutes(now()) <= 60 &&
               !$this->is_deleted;
    }

    /**
     * Check if user can delete this message.
     */
    public function canDelete(int $userId): bool
    {
        return $this->user_id === $userId && !$this->is_deleted;
    }

    /**
     * Edit the message.
     */
    public function editMessage(string $newMessage): void
    {
        $this->update([
            'message' => $newMessage,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Soft delete the message (mark as deleted).
     */
    public function softDeleteMessage(): void
    {
        $this->update([
            'is_deleted' => true,
            'message' => 'This message was deleted',
        ]);
    }

  

    /**
     * Get formatted attachments.
     */
    public function getFormattedAttachmentsAttribute(): array
    {
        if (!$this->attachments) {
            return [];
        }

        return collect($this->attachments)->map(function ($attachment) {
            return [
                'name' => $attachment['name'] ?? '',
                'type' => $attachment['type'] ?? '',
                'size' => $attachment['size'] ?? 0,
                'url' => isset($attachment['path']) 
                    ? asset('storage/' . $attachment['path']) 
                    : null,
            ];
        })->toArray();
    }

    /**
     * Check if message has attachments.
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments) || !empty($this->attachment_url);
    }

    /**
     * Get attachment URL with full path.
     */
    public function getFullAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_url) {
            return null;
        }

        // If already a full URL, return as is
        if (str_starts_with($this->attachment_url, 'http')) {
            return $this->attachment_url;
        }

        // Otherwise, prepend the app URL
        return asset($this->attachment_url);
    }

    /**
     * Check if this is a system message.
     */
    public function isSystemMessage(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // When a message is created, update the group's updated_at
        static::created(function ($message) {
            $message->chatGroup->touch();
        });

        // When a message is updated, update the group's updated_at
        static::updated(function ($message) {
            if (!$message->wasRecentlyCreated) {
                $message->chatGroup->touch();
            }
        });
    }
}
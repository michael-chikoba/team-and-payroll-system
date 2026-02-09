<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Jobs\SendPushNotification;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action',
        'data',
        'is_read',
        'read_at',
        'notifiable_id',
        'notifiable_type'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-dispatch push notification when created
        static::created(function ($notification) {
            // Check if push notifications are enabled
            if (config('push-notifications.queue.enabled', true)) {
                // Queue the push notification
                SendPushNotification::dispatch($notification->user, $notification);
            }
        });
    }

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable entity (polymorphic relationship)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get only unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get only read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to filter by notification type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get recent notifications
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark this notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark this notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }

    /**
     * Send push notification manually
     * Use this if you want to send push after creation without auto-dispatch
     */
    public function sendPushNotification(): void
    {
        SendPushNotification::dispatch($this->user, $this);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Get formatted time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        $icons = [
            'business_group_invitation' => 'users',
            'task_assigned' => 'clipboard-check',
            'schedule_updated' => 'calendar',
            'leave_request' => 'calendar-days',
            'ticket_created' => 'ticket',
            'reminder' => 'bell',
            'system_announcement' => 'megaphone',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get notification color/class based on type
     */
    public function getColorAttribute(): string
    {
        $colors = [
            'business_group_invitation' => 'purple',
            'task_assigned' => 'blue',
            'schedule_updated' => 'pink',
            'leave_request' => 'indigo',
            'ticket_created' => 'yellow',
            'reminder' => 'orange',
            'system_announcement' => 'green',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    /**
     * Static method to create and send notification
     */
    public static function createAndSend(array $data): self
    {
        $notification = self::create($data);
        // Push notification is auto-sent via boot method
        return $notification;
    }

    /**
     * Static method to create without sending push
     */
    public static function createWithoutPush(array $data): self
    {
        // Temporarily disable auto-dispatch
        return self::withoutEvents(function () use ($data) {
            return self::create($data);
        });
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsReadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Delete old read notifications
     */
    public static function deleteOldReadNotifications(int $days = 30): int
    {
        return self::where('is_read', true)
            ->where('read_at', '<=', now()->subDays($days))
            ->delete();
    }

    /**
     * Get unread count for a user
     */
    public static function unreadCountForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}
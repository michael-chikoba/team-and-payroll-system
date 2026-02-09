<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'push_enabled',
        'email_enabled',
        'push_business_group_invitation',
        'push_task_assigned',
        'push_schedule_updated',
        'push_leave_request',
        'push_ticket_created',
        'push_reminder',
        'push_system_announcement',
        'email_business_group_invitation',
        'email_task_assigned',
        'email_schedule_updated',
        'email_leave_request',
        'email_ticket_created',
        'email_reminder',
        'email_system_announcement',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end'
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_business_group_invitation' => 'boolean',
        'push_task_assigned' => 'boolean',
        'push_schedule_updated' => 'boolean',
        'push_leave_request' => 'boolean',
        'push_ticket_created' => 'boolean',
        'push_reminder' => 'boolean',
        'push_system_announcement' => 'boolean',
        'email_business_group_invitation' => 'boolean',
        'email_task_assigned' => 'boolean',
        'email_schedule_updated' => 'boolean',
        'email_leave_request' => 'boolean',
        'email_ticket_created' => 'boolean',
        'email_reminder' => 'boolean',
        'email_system_announcement' => 'boolean',
        'quiet_hours_enabled' => 'boolean'
    ];

    /**
     * Get the user that owns the preferences
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if push notifications are enabled for a specific type
     */
    public function isPushEnabledFor(string $type): bool
    {
        if (!$this->push_enabled) {
            return false;
        }

        $key = 'push_' . $type;
        return $this->$key ?? false;
    }

    /**
     * Check if email notifications are enabled for a specific type
     */
    public function isEmailEnabledFor(string $type): bool
    {
        if (!$this->email_enabled) {
            return false;
        }

        $key = 'email_' . $type;
        return $this->$key ?? false;
    }

    /**
     * Check if currently in quiet hours
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled || !$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i:s');
        return $now >= $this->quiet_hours_start && $now <= $this->quiet_hours_end;
    }

    /**
     * Get default preferences
     */
    public static function getDefaults(): array
    {
        return [
            'push_enabled' => true,
            'email_enabled' => true,
            'push_business_group_invitation' => true,
            'push_task_assigned' => true,
            'push_schedule_updated' => true,
            'push_leave_request' => true,
            'push_ticket_created' => true,
            'push_reminder' => true,
            'push_system_announcement' => true,
            'email_business_group_invitation' => true,
            'email_task_assigned' => true,
            'email_schedule_updated' => false,
            'email_leave_request' => true,
            'email_ticket_created' => false,
            'email_reminder' => true,
            'email_system_announcement' => true,
            'quiet_hours_enabled' => false,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'channel_type',
        'chat_group_id',
        'email_address',
        'slack_webhook_url',
        'webhook_url',
        'subscribed_events',
        'filters',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'subscribed_events' => '[]',
        'filters' => '{}',
    ];

    // ===== Relationships =====
    
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ===== Scopes =====
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeSubscribedTo($query, $eventType)
    {
        return $query->whereJsonContains('subscribed_events', $eventType);
    }

    public function scopeByChannelType($query, $type)
    {
        return $query->where('channel_type', $type);
    }

    // ===== Helper Methods =====
    
    /**
     * Check if this channel is subscribed to an event
     */
    public function isSubscribedTo(string $eventType): bool
    {
        $events = $this->subscribed_events ?? [];
        return in_array($eventType, $events);
    }

    /**
     * Check if a ticket matches the channel's filters
     */
    public function matchesFilters(Ticket $ticket): bool
    {
        $filters = $this->filters ?? [];
        
        // No filters means all tickets match
        if (empty($filters)) {
            return true;
        }

        // Check ticket type filter
        if (isset($filters['ticket_types']) && !empty($filters['ticket_types'])) {
            if (!in_array($ticket->type, $filters['ticket_types'])) {
                return false;
            }
        }

        // Check priority filter
        if (isset($filters['priorities']) && !empty($filters['priorities'])) {
            if (!in_array($ticket->priority, $filters['priorities'])) {
                return false;
            }
        }

        // Check status filter
        if (isset($filters['statuses']) && !empty($filters['statuses'])) {
            if (!in_array($ticket->status, $filters['statuses'])) {
                return false;
            }
        }

        // Check category filter
        if (isset($filters['categories']) && !empty($filters['categories'])) {
            if (!in_array($ticket->category, $filters['categories'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available event types
     */
    public static function getAvailableEvents(): array
    {
        return [
            'ticket.created' => 'Ticket Created',
            'ticket.updated' => 'Ticket Updated',
            'ticket.status_changed' => 'Status Changed',
            'ticket.assigned' => 'Ticket Assigned',
            'ticket.comment_added' => 'Comment Added',
            'ticket.attachment_uploaded' => 'Attachment Uploaded',
            'ticket.priority_changed' => 'Priority Changed',
            'ticket.approved' => 'Ticket Approved',
            'ticket.rejected' => 'Ticket Rejected',
        ];
    }

    /**
     * Get channel type display name
     */
    public function getChannelTypeDisplayName(): string
    {
        return match($this->channel_type) {
            'chat_group' => 'Chat Group',
            'email' => 'Email',
            'slack' => 'Slack',
            'webhook' => 'Webhook',
            default => ucfirst($this->channel_type),
        };
    }

    /**
     * Get channel destination (for display)
     */
    public function getDestination(): string
    {
        return match($this->channel_type) {
            'chat_group' => $this->chatGroup->name ?? 'Unknown Group',
            'email' => $this->email_address ?? 'No email set',
            'slack' => 'Slack Workspace',
            'webhook' => $this->webhook_url ?? 'No URL set',
            default => 'Not configured',
        };
    }
}
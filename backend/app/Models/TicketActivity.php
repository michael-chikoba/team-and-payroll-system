<?php
// app/Models/TicketActivity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'description',
        'changes',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'formatted_created_at',
        'user_name',
        'icon_class',
        'color_class'
    ];

    /**
     * Relationships
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->select(['id', 'first_name', 'last_name', 'email']);
    }

    /**
     * Accessors
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    public function getUserNameAttribute(): string
    {
        if (!$this->user) return 'System';
        
        return trim($this->user->first_name . ' ' . $this->user->last_name) ?: 
               $this->user->email;
    }

    public function getIconClassAttribute(): string
    {
        return match($this->action) {
            'created' => 'plus-circle',
            'updated' => 'pencil',
            'status_changed' => 'arrows-up-down',
            'commented' => 'chat-bubble-left',
            'attachment_added' => 'paper-clip',
            'priority_changed' => 'exclamation-triangle',
            'assigned' => 'user-plus',
            'reassigned' => 'user-group',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'resolved' => 'check-badge',
            'closed' => 'lock-closed',
            'reopened' => 'arrow-path',
            default => 'document-text'
        };
    }

    public function getColorClassAttribute(): string
    {
        return match($this->action) {
            'created' => 'bg-blue-100 text-blue-800',
            'status_changed' => 'bg-purple-100 text-purple-800',
            'commented' => 'bg-green-100 text-green-800',
            'attachment_added' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-800',
            'resolved' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Scopes
     */
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
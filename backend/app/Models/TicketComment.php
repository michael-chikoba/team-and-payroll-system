<?php
// app/Models/TicketComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class TicketComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
        'is_internal',
        'attachments'
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_created_at',
        'user_name',
        'can_edit',
        'can_delete'
    ];

    /**
     * Boot method to handle soft deletes safely
     */
    protected static function boot()
    {
        parent::boot();

        // Only apply soft delete scope if the column exists
        static::addGlobalScope('soft_deletes', function ($builder) {
            $instance = new static;
            if (Schema::hasColumn($instance->getTable(), 'deleted_at')) {
                $builder->whereNull($instance->getTable() . '.deleted_at');
            }
        });
    }

    /**
     * Override delete to handle missing deleted_at column
     */
    public function delete()
    {
        $instance = new static;
        if (Schema::hasColumn($instance->getTable(), 'deleted_at')) {
            return parent::delete();
        } else {
            // If no soft deletes, force delete
            return $this->forceDelete();
        }
    }

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
            ->select(['id', 'first_name', 'last_name', 'email', 'role']);
    }

    public function attachments(): HasMany
    {
        // FIXED: Explicitly specify 'comment_id' as the foreign key
        return $this->hasMany(TicketAttachment::class, 'comment_id');
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
        if (!$this->user) return 'Unknown User';
        
        return trim($this->user->first_name . ' ' . $this->user->last_name) ?: 
               $this->user->email;
    }

    public function getCanEditAttribute(): bool
    {
        if (!auth()->check()) return false;
        
        $user = auth()->user();
        
        // Users can edit their own comments within 15 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 15;
        }
        
        // Admins can edit any comment
        return $user->hasRole('admin');
    }

    public function getCanDeleteAttribute(): bool
    {
        if (!auth()->check()) return false;
        
        $user = auth()->user();
        
        // Users can delete their own comments within 15 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 15;
        }
        
        // Admins can delete any comment
        return $user->hasRole('admin');
    }

    /**
     * Scopes
     */
    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        return $query->where('is_internal', false);
    }

    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }
}
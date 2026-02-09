<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessGroupActivityLog extends Model
{
    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'business_group_id',
        'business_id',
        'user_id',
        'action',
        'description',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessLimitHistory extends Model
{
    use HasFactory;

    protected $table = 'business_limits_history';

    protected $fillable = [
        'business_id',
        'changed_by_admin_id',
        'old_limit',
        'new_limit',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the business that owns this limit history.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the admin who changed the limit.
     */
    public function changedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_admin_id');
    }

    /**
     * Record a limit change.
     */
    public static function recordChange(
        int $businessId,
        int $adminId,
        int $oldLimit,
        int $newLimit,
        ?string $reason = null
    ): self {
        return self::create([
            'business_id' => $businessId,
            'changed_by_admin_id' => $adminId,
            'old_limit' => $oldLimit,
            'new_limit' => $newLimit,
            'reason' => $reason,
        ]);
    }
}
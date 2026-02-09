<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'admin_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the business that owns the activity log.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Create a new activity log entry.
     */
    public static function logActivity(
        int $businessId,
        ?int $adminId,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'business_id' => $businessId,
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
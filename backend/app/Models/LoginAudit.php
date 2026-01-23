<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAudit extends Model
{
    public $timestamps = false; // We use custom login_at/logout_at
    
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'country_code',
        'region',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'status',
        'failure_reason',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable location
     */
    public function getLocationAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->region,
            $this->country
        ]);

        return implode(', ', $parts) ?: 'Unknown Location';
    }

    /**
     * Get session duration in minutes
     */
    public function getSessionDurationAttribute(): ?int
    {
        if (!$this->logout_at) {
            return null;
        }

        return $this->login_at->diffInMinutes($this->logout_at);
    }

    /**
     * Check if login is suspicious
     */
    public function isSuspicious(): bool
    {
        // Check for unusual patterns
        $lastLogin = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'success')
            ->latest('login_at')
            ->first();

        if (!$lastLogin) {
            return false;
        }

        // Different country within short time
        if ($lastLogin->country_code !== $this->country_code) {
            $timeDiff = $lastLogin->login_at->diffInHours($this->login_at);
            if ($timeDiff < 2) { // Login from different country in < 2 hours
                return true;
            }
        }

        return false;
    }
}
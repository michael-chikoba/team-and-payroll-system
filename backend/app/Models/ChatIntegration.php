<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChatIntegration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chat_group_id',
        'created_by',
        'name',
        'description',
        'api_key',
        'webhook_secret',
        'icon_url',
        'is_active',
        'permissions',
        'settings',
        'message_count',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'webhook_secret',
    ];

    /**
     * Relationships
     */
    public function chatGroup()
    {
        return $this->belongsTo(ChatGroup::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(ChatIntegrationLog::class);
    }

    /**
     * Generate a unique API key
     */
    public static function generateApiKey(): string
    {
        do {
            $key = 'chat_' . Str::random(48);
        } while (self::where('api_key', $key)->exists());

        return $key;
    }

    /**
     * Generate a webhook secret
     */
    public static function generateWebhookSecret(): string
    {
        return Str::random(32);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('message_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Log an activity
     */
    public function logActivity(string $action, string $status, ?string $payload = null, ?string $errorMessage = null): void
    {
        ChatIntegrationLog::create([
            'chat_integration_id' => $this->id,
            'action' => $action,
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Check if integration can send messages
     */
    public function canSendMessages(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check rate limiting (100 messages per hour)
        $recentMessages = $this->logs()
            ->where('action', 'message_sent')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentMessages < 100;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(?string $signature, string $payload): bool
    {
        if (!$this->webhook_secret || !$signature) {
            return true; // No signature verification required
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGroup($query, int $groupId)
    {
        return $query->where('chat_group_id', $groupId);
    }
}
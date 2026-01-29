<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'workspace_name',
        'workspace_id',
        'channel_id',
        'channel_name',
        'access_token',
        'webhook_url',
        'notification_settings',
        'is_active',
        'connected_at',
        'connected_by',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'webhook_url',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function connectedBy()
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function logs()
    {
        return $this->hasMany(SlackNotificationLog::class);
    }

    public function shouldNotify($notificationType)
    {
        if (!$this->is_active) {
            return false;
        }

        $settings = $this->notification_settings ?? [];
        return $settings[$notificationType] ?? true;
    }
}
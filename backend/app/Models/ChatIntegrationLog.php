<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatIntegrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_integration_id',
        'action',
        'payload',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
    ];

    public function integration()
    {
        return $this->belongsTo(ChatIntegration::class, 'chat_integration_id');
    }

    /**
     * Scopes
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
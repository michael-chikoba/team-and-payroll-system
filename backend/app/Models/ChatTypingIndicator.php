<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatTypingIndicator extends Model
{
    protected $fillable = [
        'chat_group_id',
        'user_id',
        'last_typed_at',
    ];

    protected $casts = [
        'last_typed_at' => 'datetime',
    ];

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTyping(): bool
    {
        return $this->last_typed_at &&
            $this->last_typed_at->diffInSeconds(now()) < 5;
    }
}

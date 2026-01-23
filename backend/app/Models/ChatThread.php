<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    protected $fillable = [
        'chat_group_id',
        'parent_message_id',
        'last_reply_id',
        'reply_count',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class);
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_message_id');
    }

    public function lastReply(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_reply_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function incrementReplyCount(): void
    {
        $this->increment('reply_count');
        $this->update(['last_reply_at' => now()]);
    }
}

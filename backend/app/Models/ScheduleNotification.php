<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'type',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
}
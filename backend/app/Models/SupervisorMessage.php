<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupervisorMessage
 *
 * Represents a single message in the private thread between
 * an employee and their direct supervisor.
 *
 * @property int         $id
 * @property int         $employee_id
 * @property int         $supervisor_user_id
 * @property int         $sender_id
 * @property string      $message
 * @property string|null $category
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $supervisor
 * @property-read User $sender
 * @property-read Employee $employee
 */
class SupervisorMessage extends Model
{
    use SoftDeletes;

    protected $table = 'supervisor_messages';

    protected $fillable = [
        'employee_id',
        'supervisor_user_id',
        'sender_id',
        'message',
        'category',
        'read_at',
    ];

    protected $casts = [
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ── Accessors / helpers ───────────────────────────────────────

    /**
     * Whether this message has been read by the recipient.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Whether the message is still within the deletable window (5 minutes).
     */
    public function isDeletable(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 5;
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: messages in a specific thread.
     */
    public function scopeForThread($query, int $employeeId, int $supervisorUserId)
    {
        return $query
            ->where('employee_id', $employeeId)
            ->where('supervisor_user_id', $supervisorUserId);
    }

    /**
     * Scope: unread messages not sent by the given user.
     */
    public function scopeUnreadFor($query, int $userId)
    {
        return $query
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at');
    }
}
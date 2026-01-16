<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskWorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'hours',
        'description',
        'work_date',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'work_date' => 'date',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
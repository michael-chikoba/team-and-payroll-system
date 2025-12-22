<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'start_date',        // Added start_date
        'due_date',
        'assigned_to',
        'assigned_user_id',
        'metadata',
        'notes',
        'status',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected $appends = ['is_overdue'];

    // Relationships
    public function notifications()
    {
        return $this->hasMany(ScheduleNotification::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->status !== 'completed' && 
               $this->due_date < Carbon::now();
    }

    // Accessor to map metadata to meta_data for frontend compatibility
    public function getMetaDataAttribute()
    {
        return $this->metadata;
    }

    // Accessor to map start_date to scheduled_date for frontend compatibility
    public function getScheduledDateAttribute()
    {
        return $this->start_date;
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
                    ->where('due_date', '<', Carbon::now());
    }

    public function scopeUpcoming($query, $hours = 24)
    {
        return $query->where('status', '!=', 'completed')
                    ->whereBetween('due_date', [
                        Carbon::now(),
                        Carbon::now()->addHours($hours)
                    ]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAssignedTo($query, $person)
    {
        return $query->where('assigned_to', $person);
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => Carbon::now()
        ]);

        $this->notifications()->create([
            'type' => 'completed',
            'message' => "Task '{$this->title}' has been completed."
        ]);
    }

    public function updateStatus()
    {
        if ($this->status !== 'completed' && $this->due_date < Carbon::now()) {
            $this->update(['status' => 'overdue']);
        }
    }
}
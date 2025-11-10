<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department',
        'max_team_size',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the user that owns the manager record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all employees in this manager's department.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department', 'department');
    }

    /**
     * Get all employees directly assigned to this manager.
     */
    public function directReports()
    {
        return $this->hasMany(Employee::class, 'manager_id', 'user_id');
    }

    /**
     * Get the count of employees in this department.
     */
    public function getTeamSizeAttribute()
    {
        return $this->employees()->count();
    }
}
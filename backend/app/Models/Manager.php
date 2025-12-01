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
        'business_id',    // Add this
        'country_id',     // Add this
    ];

    protected $casts = [
        'permissions' => 'array',
        'max_team_size' => 'integer'
    ];

    /**
     * Get the user that owns the manager record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business associated with the manager.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the country associated with the manager.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all employees in this manager's department AND business.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department', 'department')
                    ->where('business_id', $this->business_id);
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

    /**
     * Scope a query to filter by business.
     */
    public function scopeInBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }
}
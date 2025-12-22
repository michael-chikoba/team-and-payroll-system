<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'current_business_id', // Add this if not already there
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    /**
     * Get the employee profile associated with the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
    
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'super_admin' || $this->role === 'owner';
    }
    
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
    
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }
    
    /**
     * Get all business admin records for this user
     */
    public function businessAdmins(): HasMany
    {
        return $this->hasMany(BusinessAdmin::class);
    }
    
 // THIS IS WRONG - Business can't have a businesses() relationship to itself!
public function businesses(): BelongsToMany
{
    return $this->belongsToMany(Business::class, 'business_admins')
                ->withPivot('role', 'is_primary')
                ->withTimestamps();
}
    /**
     * Get the user's department via their employee record.
     */
    public function getDepartmentAttribute()
    {
        // Assuming 'employee' is the relationship method on User model
        return $this->employee ? $this->employee->department : null;
    }

    /**
     * Check if user belongs to a specific department.
     */
    public function hasDepartment(string $department): bool
    {
        // Allow Super Admins to bypass department checks
        if ($this->hasRole('admin') || $this->hasRole('super-admin')) {
            return true;
        }

        return $this->department === $department;
    }
    /**
     * Get the current business the user is working in
     */
    public function currentBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }
    
    /**
     * Check if user owns any business
     */
    public function getIsBusinessOwnerAttribute(): bool
    {
        return $this->businessAdmins()->where('role', 'owner')->exists();
    }
    
    /**
     * Switch to a different business context
     */
    public function switchBusiness(Business $business): void
    {
        if ($this->businesses()->where('businesses.id', $business->id)->exists()) {
            $this->update(['current_business_id' => $business->id]);
        }
    }
}
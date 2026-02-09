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
use App\Traits\ManagesTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, ManagesTokens;
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'current_business_id',
        'business_id', // Add this for fallback
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
    
    /**
     * Check if user is an admin (includes super_admin and owner)
     * DOES NOT include managers
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'owner']);
    }
    public function pushSubscriptions()
{
    return $this->hasMany(PushSubscription::class);
}

public function notificationPreferences()
{
    return $this->hasOne(NotificationPreference::class);
}
    /**
     * Check if user is a manager
     * Returns true ONLY for manager role (not admin)
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
    
    /**
     * Check if user is an employee
     */
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }
    
    /**
     * Check if user has manager-level permissions (manager OR admin)
     * Use this when you want to allow both managers and admins
     */
    public function hasManagerPermissions(): bool
    {
        return $this->isManager() || $this->isAdmin();
    }
    
    /**
     * Get all business admin records for this user
     */
    public function businessAdmins(): HasMany
    {
        return $this->hasMany(BusinessAdmin::class);
    }
    
    /**
     * Get all businesses this user has admin access to
     */
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
        return $this->employee ? $this->employee->department : null;
    }

    /**
     * Check if user belongs to a specific department.
     */
    public function hasDepartment(string $department): bool
    {
        // Allow Admins to bypass department checks
        if ($this->isAdmin()) {
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

    /**
     * Chat relationships
     */
    public function chatGroups(): BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_members')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function createdChatGroups(): HasMany
    {
        return $this->hasMany(ChatGroup::class, 'created_by');
    }
}
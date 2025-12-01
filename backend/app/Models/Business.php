<?php
// app/Models/Business.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'registration_number',
        'tax_identification_number',
        'business_type',
        'industry',
        'website',
        'logo_path',
        'email',
        'phone',
        'fax',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_id',
        'currency_code',
        'fiscal_year_start',
        'pay_period',
        'status',
        'is_verified',
    ];

    protected $casts = [
        'fiscal_year_start' => 'date',
        'is_verified' => 'boolean',
    ];

    protected $appends = [
        'full_address',
    ];

    /**
     * ========================================
     * RELATIONSHIPS
     * ========================================
     */

    /**
     * Get the country that the business belongs to
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all admins for the business (through business_admins pivot table)
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_admins')
                    ->withPivot('role', 'is_primary')
                    ->withTimestamps();
    }

    /**
     * Get the primary admin for the business
     */
    public function primaryAdmin()
    {
        return $this->admins()
                    ->wherePivot('is_primary', true)
                    ->withPivot('role', 'is_primary');
    }

    /**
     * Get all employees for the business
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all payrolls for the business
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * ========================================
     * SCOPES
     * ========================================
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to filter businesses by country
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to search businesses by name or legal name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
    }

    /**
     * ========================================
     * ACCESSORS
     * ========================================
     */

    /**
     * Get the full address of the business
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->postal_code;
        
        if ($this->country) {
            $address .= ', ' . $this->country->name;
        }
        
        return $address;
    }

    /**
     * Get formatted business type
     */
    public function getFormattedBusinessTypeAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->business_type));
    }

    /**
     * ========================================
     * BUSINESS METHODS
     * ========================================
     */

    /**
     * Make a user the primary admin of the business
     */
    public function makePrimaryAdmin(User $user): void
    {
        // Remove existing primary admin
        $this->admins()->wherePivot('is_primary', true)->update(['is_primary' => false]);
        
        // Check if user is already an admin
        if ($this->admins()->where('user_id', $user->id)->exists()) {
            // Update existing admin to primary
            $this->admins()->updateExistingPivot($user->id, [
                'is_primary' => true,
                'role' => 'owner',
                'updated_at' => now()
            ]);
        } else {
            // Add new admin as primary
            $this->admins()->attach($user->id, [
                'role' => 'owner',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Add an admin to the business
     */
    public function addAdmin(User $user, string $role = 'admin', bool $isPrimary = false): void
    {
        if ($isPrimary) {
            $this->makePrimaryAdmin($user);
            return;
        }

        if (!$this->admins()->where('user_id', $user->id)->exists()) {
            $this->admins()->attach($user->id, [
                'role' => $role,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Remove an admin from the business
     */
    public function removeAdmin(User $user): bool
    {
        $admin = $this->admins()->where('user_id', $user->id)->first();
        
        // Prevent removing the only primary admin
        if ($admin && $admin->pivot->is_primary && $this->admins()->wherePivot('is_primary', true)->count() <= 1) {
            return false;
        }

        $this->admins()->detach($user->id);
        return true;
    }

    /**
     * Check if a user is an admin of the business
     */
    public function hasAdmin(User $user): bool
    {
        return $this->admins()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the primary admin of the business
     */
    public function isPrimaryAdmin(User $user): bool
    {
        return $this->admins()
                    ->where('user_id', $user->id)
                    ->wherePivot('is_primary', true)
                    ->exists();
    }

    /**
     * Get business statistics
     */
    public function getStats(): array
    {
        return [
            'total_employees' => $this->employees()->count(),
            'active_employees' => $this->employees()->where('is_active', true)->count(),
            'total_admins' => $this->admins()->count(),
            'departments' => $this->employees()->distinct('department')->pluck('department'),
            'total_payrolls' => $this->payrolls()->count(),
        ];
    }

    /**
     * Check if business can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->employees()->count() === 0 && 
               $this->payrolls()->count() === 0;
    }

    /**
     * Get default settings for the business
     */
    public function getDefaultSettings(): array
    {
        return [
            'currency' => $this->currency_code,
            'pay_period' => $this->pay_period,
            'country_id' => $this->country_id,
            'fiscal_year_start' => $this->fiscal_year_start,
        ];
    }
}
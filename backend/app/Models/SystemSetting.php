<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'key',
        'value',
        'description',
        'country_code'
    ];

    protected $casts = [
        'business_id' => 'integer',
    ];

    /**
     * Get business relationship
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get country relationship  
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Get a setting value with proper business and country scoping
     */
    public static function getSetting(string $key, ?int $businessId = null, ?string $countryCode = null)
    {
        Log::info('SYSTEM_SETTING: getSetting called', [
            'key' => $key,
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);

        // Build query with priority: business-specific > country-specific > global
        $query = self::where('key', $key);
        
        // Try business-specific first
        if ($businessId && $countryCode) {
            $setting = (clone $query)
                ->where('business_id', $businessId)
                ->where('country_code', $countryCode)
                ->first();
            
            if ($setting) {
                Log::info('SYSTEM_SETTING: Found business-specific setting', [
                    'key' => $key,
                    'business_id' => $businessId,
                    'country_code' => $countryCode,
                    'value' => $setting->value
                ]);
                return self::parseValue($setting->value);
            }
        }
        
        // Try country-specific
        if ($countryCode) {
            $setting = (clone $query)
                ->whereNull('business_id')
                ->where('country_code', $countryCode)
                ->first();
            
            if ($setting) {
                Log::info('SYSTEM_SETTING: Found country-specific setting', [
                    'key' => $key,
                    'country_code' => $countryCode,
                    'value' => $setting->value
                ]);
                return self::parseValue($setting->value);
            }
        }
        
        // Fallback to global
        $setting = (clone $query)
            ->whereNull('business_id')
            ->where('country_code', 'global')
            ->first();
        
        if ($setting) {
            Log::info('SYSTEM_SETTING: Found global setting', [
                'key' => $key,
                'value' => $setting->value
            ]);
            return self::parseValue($setting->value);
        }
        
        Log::warning('SYSTEM_SETTING: No setting found', [
            'key' => $key,
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);
        
        return null;
    }

    /**
     * Get all settings with proper business and country scoping
     */
    public static function getAllSettings(?int $businessId = null, ?string $countryCode = null): array
    {
        Log::info('SYSTEM_SETTING: getAllSettings called', [
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);

        $settings = [];
        
        // Get all unique keys
        $keys = self::distinct()->pluck('key');
        
        Log::info('SYSTEM_SETTING: Found keys', [
            'count' => $keys->count(),
            'keys' => $keys->toArray()
        ]);
        
        foreach ($keys as $key) {
            $value = self::getSetting($key, $businessId, $countryCode);
            if ($value !== null) {
                $settings[$key] = $value;
            }
        }
        
        Log::info('SYSTEM_SETTING: Settings compiled', [
            'count' => count($settings),
            'keys' => array_keys($settings)
        ]);
        
        return $settings;
    }

    /**
     * Set a setting value
     */
    public static function setSetting(
        string $key, 
        $value, 
        ?int $businessId = null, 
        ?string $countryCode = 'global'
    ): self {
        Log::info('SYSTEM_SETTING: setSetting called', [
            'key' => $key,
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'value_type' => gettype($value)
        ]);

        // Convert value to string if it's an array
        if (is_array($value)) {
            $value = json_encode($value);
        }

        // Find or create the setting
        $setting = self::updateOrCreate(
            [
                'key' => $key,
                'business_id' => $businessId,
                'country_code' => $countryCode
            ],
            [
                'value' => $value,
                'description' => self::getDefaultDescription($key)
            ]
        );

        // Clear cache
        $cacheKey = $businessId 
            ? "system_settings_{$businessId}_{$countryCode}" 
            : "system_settings_{$countryCode}";
        Cache::forget($cacheKey);
        
        Log::info('SYSTEM_SETTING: Setting saved', [
            'id' => $setting->id,
            'key' => $key,
            'business_id' => $businessId,
            'country_code' => $countryCode
        ]);

        return $setting;
    }

    /**
     * Parse value (handle JSON)
     */
    private static function parseValue($value)
    {
        if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }

    /**
     * Get default description for a setting key
     */
    private static function getDefaultDescription(string $key): string
    {
        $descriptions = [
            'company_name' => 'Company name for the system',
            'company_address' => 'Company physical address',
            'tax_id' => 'Company tax identification number',
            'currency' => 'Default currency for payroll',
            'annual_leave_days' => 'Default annual leave days per year',
            'sick_leave_days' => 'Default sick leave days per year',
            'maternity_leave_days' => 'Default maternity leave days',
            'paternity_leave_days' => 'Default paternity leave days',
            'default_password' => 'Default password for new employees',
            'max_login_attempts' => 'Maximum login attempts before lockout',
            'session_timeout' => 'Session timeout in minutes',
            'date_format' => 'Default date format for display',
            'departments' => 'Available departments in the company',
        ];
        
        return $descriptions[$key] ?? 'No description available';
    }

    /**
     * Scope query to business
     */
    public function scopeForBusiness($query, ?int $businessId)
    {
        if ($businessId) {
            return $query->where('business_id', $businessId);
        }
        return $query->whereNull('business_id');
    }
/**
     * Get the list of valid departments for a business/country.
     * 
     * @param int|null $businessId
     * @param string|null $countryCode
     * @return array
     */
    public static function getValidDepartments(?int $businessId = null, ?string $countryCode = null): array
    {
        $departments = self::getSetting('departments', $businessId, $countryCode);

        // Ensure we always return an array, even if setting is missing
        if (is_string($departments)) {
            // Handle comma-separated strings just in case
            return explode(',', $departments); 
        }

        return is_array($departments) ? $departments : [];
    }
    /**
     * Scope query to country
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }
}
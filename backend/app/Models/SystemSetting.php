<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    protected $casts = [
        'value' => 'string'
    ];

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(): array
    {
        $settings = self::all()->pluck('value', 'key')->toArray();
        
        // Parse JSON fields
        if (isset($settings['departments'])) {
            $settings['departments'] = json_decode($settings['departments'], true) ?? [];
        }
        
        // Ensure all required fields exist with defaults
        return array_merge([
            'company_name' => 'Payroll System',
            'company_address' => '',
            'tax_id' => '',
            'currency' => 'ZMW',
            'annual_leave_days' => 21,
            'sick_leave_days' => 7,
            'maternity_leave_days' => 90,
            'paternity_leave_days' => 14,
            'default_password' => 'Password123!',
            'max_login_attempts' => 5,
            'session_timeout' => 60,
            'departments' => [
                ['name' => 'IT'],
                ['name' => 'HR'],
                ['name' => 'Finance'],
                ['name' => 'Sales'],
                ['name' => 'Marketing'],
                ['name' => 'Operations']
            ]
        ], $settings);
    }

    /**
     * Get a specific setting value
     */
    public static function getSetting(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // Handle JSON fields
        if (in_array($key, ['departments'])) {
            return json_decode($setting->value, true) ?? $default;
        }

        return $setting->value;
    }

    /**
     * Update or create a setting
     */
    public static function setSetting(string $key, $value): bool
    {
        // Convert arrays to JSON for storage
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return (bool) self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
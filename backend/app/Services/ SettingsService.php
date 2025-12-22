<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function getSetting(string $key, ?int $businessId, ?string $countryCode = null)
    {
        // Cache key: settings_businessID_countryCode_settingKey
        $cacheKey = "settings_" . ($businessId ?? 'global') . "_" . ($countryCode ?? 'global') . "_{$key}";
        
        return Cache::remember($cacheKey, 600, function () use ($key, $businessId, $countryCode) {
            
            // Query logic
            $settings = SystemSetting::where('key', $key)
                ->where(function($q) use ($businessId) {
                    $q->where('business_id', $businessId)
                      ->orWhereNull('business_id');
                })
                ->get();

            // 1. Business + Country
            if ($businessId && $countryCode) {
                $match = $settings->where('business_id', $businessId)
                                  ->where('country_code', $countryCode)
                                  ->first();
                if ($match) return $match->value;
            }

            // 2. Business Default
            if ($businessId) {
                $match = $settings->where('business_id', $businessId)
                                  ->filter(function ($item) {
                                      return is_null($item->country_code) || $item->country_code === 'global';
                                  })->first();
                if ($match) return $match->value;
            }

            // 3. Global Default
            $match = $settings->whereNull('business_id')->first();
            
            return $match ? $match->value : null;
        });
    }
}
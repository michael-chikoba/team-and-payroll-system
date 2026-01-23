<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Stevebauman\Location\Position;

class LocationService
{
    /**
     * API configuration
     */
    private array $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = [
            'cache_duration' => Config::get('location.cache_duration', 86400),
            'timeout' => Config::get('location.timeout', 5),
            'ipwho_api_key' => Config::get('location.ipwho_api_key', env('IPWHO_API_KEY')),
            'retry_attempts' => Config::get('location.retry_attempts', 2),
            'enable_all_features' => Config::get('location.enable_all_features', true),
            'trusted_proxies' => Config::get('location.trusted_proxies', []),
        ];
    }

    /**
     * Get comprehensive location data from IP address
     * Returns array instead of Position object for backward compatibility
     */
    public function getLocationFromIp(string $ip): array
    {
        // Don't track localhost/private IPs
        if ($this->isPrivateIp($ip)) {
            return $this->getDefaultLocation();
        }

        // Cache key with version
        $cacheKey = "location:v2:{$ip}";
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () use ($ip) {
            return $this->fetchFromIpWho($ip);
        });
    }

    /**
     * Get location as Position object (for stevebauman/location compatibility)
     */
    public function getLocationAsPosition(string $ip): ?Position
    {
        $locationData = $this->getLocationFromIp($ip);
        return $this->createPositionFromArray($locationData);
    }

    /**
     * Fetch data from ipwho.org API with fallback
     */
    private function fetchFromIpWho(string $ip): array
    {
        $data = $this->tryIpWhoApi($ip);
        
        // If ipwho.org fails, try fallback APIs
        if (!$data || empty($data['country'])) {
            Log::warning('ipwho.org failed, trying fallback APIs', ['ip' => $ip]);
            $data = $this->tryFallbackApis($ip);
        }
        
        return $data ?: $this->getDefaultLocation();
    }

    /**
     * Try ipwho.org API
     */
    private function tryIpWhoApi(string $ip): ?array
    {
        try {
            // Prepare parameters - DO NOT send apiKey if it's empty or invalid
            $params = [
                'get' => 'geoLocation,timezone',
                'lang' => 'en',
            ];

            // Only add API key if it's not empty and looks valid
            $apiKey = $this->config['ipwho_api_key'];
            if (!empty($apiKey) && str_starts_with($apiKey, 'sk.')) {
                $params['apiKey'] = $apiKey;
                Log::debug('Using ipwho.org API key', ['key_prefix' => substr($apiKey, 0, 10) . '...']);
            } else {
                Log::debug('Using ipwho.org without API key (free tier)');
            }

            // Make request
            $response = Http::timeout($this->config['timeout'])
                ->retry($this->config['retry_attempts'], 100)
                ->get("https://api.ipwho.org/ip/{$ip}", $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] === true) {
                    $geoData = $data['data']['geoLocation'] ?? [];
                    $timezoneData = $data['data']['timezone'] ?? [];

                    $location = [
                        'country' => $geoData['country'] ?? null,
                        'country_code' => $geoData['countryCode'] ?? null,
                        'continent' => $geoData['continent'] ?? null,
                        'continent_code' => $geoData['continentCode'] ?? null,
                        'region' => $geoData['region'] ?? null,
                        'region_code' => $geoData['regionCode'] ?? null,
                        'city' => $geoData['city'] ?? null,
                        'postal_code' => $geoData['postal_Code'] ?? null,
                        'latitude' => $geoData['latitude'] ?? null,
                        'longitude' => $geoData['longitude'] ?? null,
                        'accuracy_radius' => $geoData['accuracy_radius'] ?? null,
                        'capital' => $geoData['capital'] ?? null,
                        'dial_code' => $geoData['dial_code'] ?? null,
                        'is_in_eu' => $geoData['is_in_eu'] ?? false,
                        'timezone' => $timezoneData['time_zone'] ?? null,
                        'timezone_abbr' => $timezoneData['abbr'] ?? null,
                        'timezone_offset' => $timezoneData['offset'] ?? null,
                        'timezone_utc' => $timezoneData['utc'] ?? null,
                        'timezone_is_dst' => $timezoneData['is_dst'] ?? false,
                        'current_local_time' => $timezoneData['current_time'] ?? null,
                        'source' => 'ipwho.org',
                        'ip' => $ip,
                    ];

                    // Enhance with additional features if enabled
                    if ($this->config['enable_all_features']) {
                        $location = $this->enhanceLocationData($location);
                    }

                    Log::info('Successfully fetched location from ipwho.org', [
                        'ip' => $ip,
                        'country' => $location['country'],
                        'city' => $location['city']
                    ]);

                    return $location;
                } else {
                    // API returned success: false
                    Log::warning('ipwho.org API returned failure', [
                        'ip' => $ip,
                        'message' => $data['message'] ?? 'Unknown error',
                        'success' => $data['success'] ?? false
                    ]);
                    return null;
                }
            } else {
                Log::warning('ipwho.org API request failed', [
                    'ip' => $ip,
                    'status' => $response->status(),
                    'response' => substr($response->body(), 0, 200) // Limit response size
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('ipwho.org API error', [
                'ip' => $ip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Try fallback APIs
     */
    private function tryFallbackApis(string $ip): ?array
    {
        // Try ipapi.co first
        $data = $this->tryIpApiCo($ip);
        if ($data) return $data;

        // Try ip-api.com second
        $data = $this->tryIpApiCom($ip);
        if ($data) return $data;

        return null;
    }

    /**
     * Try ipapi.co (fallback 1)
     */
    private function tryIpApiCo(string $ip): ?array
    {
        try {
            $response = Http::timeout($this->config['timeout'])
                ->get("https://ipapi.co/{$ip}/json/");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'region' => $data['region'] ?? null,
                    'city' => $data['city'] ?? null,
                    'postal_code' => $data['postal'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'source' => 'ipapi.co',
                    'ip' => $ip,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('ipapi.co fallback failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Try ip-api.com (fallback 2)
     */
    private function tryIpApiCom(string $ip): ?array
    {
        try {
            $response = Http::timeout($this->config['timeout'])
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query'
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'postal_code' => $data['zip'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                        'isp' => $data['isp'] ?? $data['org'] ?? null,
                        'source' => 'ip-api.com',
                        'ip' => $ip,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('ip-api.com fallback failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Create Position instance from array data
     */
    private function createPositionFromArray(array $data): Position
    {
        $position = new Position();
        
        // Map our data to Position properties
        $position->countryName = $data['country'] ?? null;
        $position->countryCode = $data['country_code'] ?? null;
        $position->regionName = $data['region'] ?? null;
        $position->regionCode = $data['region_code'] ?? null;
        $position->cityName = $data['city'] ?? null;
        $position->zipCode = $data['postal_code'] ?? null;
        $position->latitude = $data['latitude'] ?? null;
        $position->longitude = $data['longitude'] ?? null;
        $position->areaCode = $data['dial_code'] ?? null;
        $position->metroCode = $data['accuracy_radius'] ?? null;
        $position->timezone = $data['timezone'] ?? null;
        $position->driver = $data['source'] ?? 'custom';
        
        // Store additional data in extra attributes
        $position->extra = array_diff_key($data, [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'region_code' => null,
            'city' => null,
            'postal_code' => null,
            'latitude' => null,
            'longitude' => null,
            'dial_code' => null,
            'accuracy_radius' => null,
            'timezone' => null,
            'source' => null,
            'ip' => null,
        ]);

        return $position;
    }

    /**
     * Enhance location data with additional information
     */
    private function enhanceLocationData(array $location): array
    {
        // Calculate accuracy in miles if available
        if (isset($location['accuracy_radius'])) {
            $location['accuracy_miles'] = round($location['accuracy_radius'] * 0.621371, 2);
        }
        
        // Add timestamp
        $location['fetched_at'] = now()->toISOString();
        
        // Add emoji flag if country code exists
        if (!empty($location['country_code']) && strlen($location['country_code']) === 2) {
            $location['flag_emoji'] = $this->getCountryFlagEmoji($location['country_code']);
        }
        
        // Add map URL
        if (!empty($location['latitude']) && !empty($location['longitude'])) {
            $location['map_url'] = sprintf(
                'https://maps.google.com/?q=%s,%s',
                $location['latitude'],
                $location['longitude']
            );
        }
        
        return $location;
    }

    /**
     * Get country flag emoji from country code
     */
    private function getCountryFlagEmoji(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);
        $flag = '';
        
        if (strlen($countryCode) === 2) {
            // Convert country code to regional indicator symbols
            for ($i = 0; $i < 2; $i++) {
                $char = $countryCode[$i];
                $flag .= mb_chr(ord($char) + 127397);
            }
        }
        
        return $flag;
    }

    /**
     * Check if IP is private/local
     */
    private function isPrivateIp(string $ip): bool
    {
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip === '::1' || 
                   substr($ip, 0, 4) === 'fe80' || // Link-local
                   substr($ip, 0, 4) === 'fc00' || // Unique local
                   substr($ip, 0, 4) === 'fd00';
        }

        // Handle IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true; // Invalid IP
        }

        // Localhost
        if ($ip === '127.0.0.1') {
            return true;
        }

        // Private ranges
        $privateRanges = [
            '10.0.0.0|10.255.255.255',      // Class A
            '172.16.0.0|172.31.255.255',    // Class B
            '192.168.0.0|192.168.255.255',  // Class C
            '169.254.0.0|169.254.255.255',  // Link-local
            '100.64.0.0|100.127.255.255',   // Carrier-grade NAT
        ];

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return true;
        }

        foreach ($privateRanges as $range) {
            [$start, $end] = explode('|', $range);
            if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default location for localhost/private IPs
     */
    private function getDefaultLocation(): array
    {
        return [
            'country' => 'Local Network',
            'country_code' => 'LN',
            'region' => null,
            'city' => 'Localhost',
            'postal_code' => null,
            'latitude' => null,
            'longitude' => null,
            'accuracy_radius' => null,
            'timezone' => config('app.timezone'),
            'capital' => null,
            'dial_code' => null,
            'is_in_eu' => false,
            'source' => 'local',
            'is_private' => true,
            'fetched_at' => now()->toISOString(),
        ];
    }

    /**
     * Get real IP address (handles proxies/load balancers)
     */
    public function getRealIp(): string
    {
        $trustedProxies = $this->config['trusted_proxies'];
        
        // Check Cloudflare first
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Check X-Forwarded-For
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ips = array_map('trim', $ips);
            
            // Remove trusted proxies from the list
            foreach ($ips as $key => $ip) {
                if (in_array($ip, $trustedProxies)) {
                    unset($ips[$key]);
                }
            }
            
            // Get the first non-proxy IP
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Check other headers
        $headers = [
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // Fallback to Laravel's request IP
        return request()->ip() ?? '127.0.0.1';
    }

    /**
     * Parse user agent to get device info
     */
    public function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';
        $isBot = false;

        // Detect bots/crawlers
        if (preg_match('/bot|crawl|spider|slurp|teoma|archive|scrape|curl|wget|python|java|php|ruby|perl/i', $userAgent)) {
            $isBot = true;
            $deviceType = 'bot';
        }

        // Detect mobile/tablet
        if (!$isBot) {
            if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                $deviceType = 'mobile';
                if (preg_match('/iPad/i', $userAgent)) {
                    $deviceType = 'tablet';
                }
            }
        }

        // Detect browser
        if (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect platform
        if (preg_match('/Windows NT 10/i', $userAgent)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6\.3/i', $userAgent)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6\.2/i', $userAgent)) {
            $platform = 'Windows 8';
        } elseif (preg_match('/Windows NT 6\.1/i', $userAgent)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS X 10_15|Mac OS X 10\.15/i', $userAgent)) {
            $platform = 'macOS Catalina';
        } elseif (preg_match('/Mac OS X 10_14|Mac OS X 10\.14/i', $userAgent)) {
            $platform = 'macOS Mojave';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'is_bot' => $isBot,
            'user_agent' => $userAgent,
        ];
    }

    /**
     * Test ipwho.org API key
     */
    public function testApiKey(): array
    {
        $apiKey = $this->config['ipwho_api_key'];
        
        if (empty($apiKey)) {
            return [
                'valid' => false,
                'message' => 'API key is empty',
                'suggestion' => 'Get a free API key from https://ipwho.org/ or remove the key to use free tier'
            ];
        }
        
        if (!str_starts_with($apiKey, 'sk.')) {
            return [
                'valid' => false,
                'message' => 'API key format is invalid (should start with sk.)',
                'key_prefix' => substr($apiKey, 0, 10) . '...'
            ];
        }
        
        // Test with a known IP
        try {
            $response = Http::timeout(3)
                ->get("https://api.ipwho.org/ip/8.8.8.8", [
                    'apiKey' => $apiKey,
                    'get' => 'geoLocation'
                ]);
                
            $data = $response->json();
            
            if (isset($data['success']) && $data['success'] === true) {
                return [
                    'valid' => true,
                    'message' => 'API key is valid',
                    'response_sample' => [
                        'country' => $data['data']['geoLocation']['country'] ?? null,
                        'city' => $data['data']['geoLocation']['city'] ?? null
                    ]
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => $data['message'] ?? 'Invalid response',
                    'response' => $data
                ];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'API test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
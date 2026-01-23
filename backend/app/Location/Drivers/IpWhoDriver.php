<?php

namespace App\Location\Drivers;

use Stevebauman\Location\Position;
use Stevebauman\Location\Drivers\Driver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpWhoDriver extends Driver
{
    /**
     * API configuration
     */
    protected array $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = [
            'timeout' => config('location.http.timeout', 3),
            'api_key' => config('location.ipwho_api_key', env('IPWHO_API_KEY')),
        ];
        
        Log::debug('IpWhoDriver initialized', [
            'has_api_key' => !empty($this->config['api_key']),
            'api_key_prefix' => $this->config['api_key'] ? substr($this->config['api_key'], 0, 10) . '...' : 'none',
            'timeout' => $this->config['timeout'],
        ]);
    }

    /**
     * Get location from IP address
     */
    public function get(string $ip): ?Position
    {
        Log::info('IpWhoDriver fetching location', [
            'ip' => $ip,
            'has_api_key' => !empty($this->config['api_key']),
        ]);

        try {
            $params = [
                'get' => 'geoLocation,timezone',
                'lang' => 'en',
            ];

            if (!empty($this->config['api_key'])) {
                $params['apiKey'] = $this->config['api_key'];
                Log::debug('Using API key for ipwho.org request', [
                    'key_prefix' => substr($this->config['api_key'], 0, 10) . '...'
                ]);
            } else {
                Log::warning('No API key configured for ipwho.org - using free tier');
            }

            Log::debug('Making request to ipwho.org API', [
                'url' => "https://api.ipwho.org/ip/{$ip}",
                'params' => $params,
                'timeout' => $this->config['timeout'],
            ]);

            $response = Http::timeout($this->config['timeout'])
                ->get("https://api.ipwho.org/ip/{$ip}", $params);

            Log::debug('ipwho.org API response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::debug('ipwho.org API response data', [
                    'success' => $data['success'] ?? false,
                    'message' => $data['message'] ?? 'No message',
                ]);

                if (isset($data['success']) && $data['success'] === true) {
                    Log::info('ipwho.org API request successful', [
                        'ip' => $ip,
                        'country' => $data['data']['geoLocation']['country'] ?? 'unknown',
                        'city' => $data['data']['geoLocation']['city'] ?? 'unknown',
                    ]);
                    return $this->hydrate($data['data']);
                } else {
                    Log::warning('ipwho.org API returned failure', [
                        'ip' => $ip,
                        'message' => $data['message'] ?? 'Unknown error',
                        'success' => $data['success'] ?? false,
                    ]);
                }
            } else {
                Log::error('ipwho.org API request failed', [
                    'ip' => $ip,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('IpWhoDriver exception', [
                'ip' => $ip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
        }

        return null;
    }

    /**
     * Hydrate the Position instance with data
     */
    protected function hydrate(array $data): Position
    {
        $geoData = $data['geoLocation'] ?? [];
        $timezoneData = $data['timezone'] ?? [];

        Log::debug('Hydrating Position with ipwho.org data', [
            'geo_data_keys' => array_keys($geoData),
            'timezone_data_keys' => array_keys($timezoneData),
        ]);

        $position = new Position();
        
        $position->countryName = $geoData['country'] ?? null;
        $position->countryCode = $geoData['countryCode'] ?? null;
        $position->regionName = $geoData['region'] ?? null;
        $position->regionCode = $geoData['regionCode'] ?? null;
        $position->cityName = $geoData['city'] ?? null;
        $position->zipCode = $geoData['postal_Code'] ?? null;
        $position->latitude = $geoData['latitude'] ?? null;
        $position->longitude = $geoData['longitude'] ?? null;
        $position->areaCode = $geoData['dial_code'] ?? null;
        $position->metroCode = $geoData['accuracy_radius'] ?? null;
        $position->timezone = $timezoneData['time_zone'] ?? null;
        $position->driver = 'ipwho';
        
        // Store additional data in extra attributes
        $position->extra = array_merge($geoData, [
            'timezone' => $timezoneData,
            'continent' => $geoData['continent'] ?? null,
            'continentCode' => $geoData['continentCode'] ?? null,
            'capital' => $geoData['capital'] ?? null,
            'is_in_eu' => $geoData['is_in_eu'] ?? false,
            'accuracy_radius' => $geoData['accuracy_radius'] ?? null,
            'timezone_abbr' => $timezoneData['abbr'] ?? null,
            'timezone_offset' => $timezoneData['offset'] ?? null,
            'timezone_utc' => $timezoneData['utc'] ?? null,
            'timezone_is_dst' => $timezoneData['is_dst'] ?? false,
            'current_local_time' => $timezoneData['current_time'] ?? null,
            'source' => 'ipwho.org',
        ]);

        return $position;
    }

    /**
     * Test the API key validity
     */
    public function testApiKey(): array
    {
        try {
            $response = Http::timeout(5)
                ->get('https://api.ipwho.org/ip/8.8.8.8', [
                    'apiKey' => $this->config['api_key'],
                    'get' => 'geoLocation',
                ]);

            $data = $response->json();
            
            return [
                'valid' => isset($data['success']) && $data['success'] === true,
                'message' => $data['message'] ?? 'No message',
                'status' => $response->status(),
                'has_api_key' => !empty($this->config['api_key']),
                'api_key_prefix' => $this->config['api_key'] ? substr($this->config['api_key'], 0, 10) . '...' : 'none',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'has_api_key' => !empty($this->config['api_key']),
            ];
        }
    }
}
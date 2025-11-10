<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'guest_id',
        'analytics_cookie',
    ];

    /**
     * Determine if the cookie should be encrypted.
     *
     * @param  string  $name
     * @param  Request  $request
     * @return bool
     */
    protected function isEncryptable($name, Request $request = null)
    {
        $shouldEncrypt = true;

        // Exclude cookies that start with 'public_'
        if (str_starts_with($name, 'public_')) {
            $shouldEncrypt = false;
        }

        // Exclude analytics cookies in development
        if (app()->environment('local') && $name === 'analytics_cookie') {
            $shouldEncrypt = false;
        }

        $finalDecision = parent::isEncryptable($name, $request);
        
        // Basic logging
        if (app()->environment('local') && !$finalDecision) {
            Log::debug('Cookie excluded from encryption', [
                'cookie_name' => $name,
                'reason' => $shouldEncrypt ? 'parent_decision' : 'custom_rule'
            ]);
        }

        return $finalDecision;
    }
}
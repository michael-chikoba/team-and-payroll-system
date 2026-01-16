<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class LogUserLogin
{
    public function handle(Login $event)
    {
        $user = $event->user;
        $ip = request()->ip();
        $userAgent = request()->header('User-Agent');

        // FOR TESTING ONLY: If IP is localhost, use a fake static IP to test location works
        if ($ip === '127.0.0.1') {
            $ip = '197.184.175.0'; // Example IP from Zambia for testing
        }

        // Get Location Data
        $position = Location::get($ip);

        // Insert into database
        DB::table('login_audits')->insert([
            'user_id'      => $user->id,
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
            'country_name' => $position ? $position->countryName : 'Unknown',
            'city_name'    => $position ? $position->cityName : 'Unknown',
            'latitude'     => $position ? $position->latitude : null,
            'longitude'    => $position ? $position->longitude : null,
            'login_at'     => now(),
        ]);

        Log::info("Audit log created for user: {$user->email} from {$ip}");
    }
}
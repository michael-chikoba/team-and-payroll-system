<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EncryptSessionData
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure session ID is not exposed in URLs
        if ($request->has('session_id')) {
            return redirect()->to($request->url());
        }

        // Log IP address for audit (already in sessions table)
        if (Auth::check()) {
            session(['ip_address' => $request->ip()]);
        }

        return $next($request);
    }
}
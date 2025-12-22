<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckDepartment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $department  The required department (e.g., 'IT', 'HR')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $department)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('login');
        }

        // 1. Check if User is Admin (Global Access)
        if ($user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        // 2. Check User's Department against the Route Requirement
        // We normalize strings to lowercase for comparison
        if (strtolower($user->department) !== strtolower($department)) {
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Department access required.'], 403);
            }

            abort(403, "Access denied. This area is restricted to the {$department} department.");
        }

        return $next($request);
    }
}
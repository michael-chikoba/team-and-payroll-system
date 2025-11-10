<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasEmployee
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if (!$request->user()->employee) {
            return response()->json([
                'message' => 'Employee profile not found. Please contact HR to set up your employee profile.',
                'error' => 'NO_EMPLOYEE_PROFILE'
            ], 403);
        }

        return $next($request);
    }
}
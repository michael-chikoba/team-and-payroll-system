<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            // For API requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // For web requests, redirect to login
            return redirect()->route('login');
        }
        
        if (!in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Insufficient permissions'], 403);
            }
            
            return redirect()->route('login')->with('error', 'Insufficient permissions');
        }
        
        return $next($request);
    }
}
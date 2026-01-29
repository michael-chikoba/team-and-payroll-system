<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user) {
            // Check if token is expired
            if ($user->isTokenExpired()) {
                return response()->json([
                    'message' => 'Token expired',
                    'expired' => true
                ], 401);
            }
            
            // Auto-refresh if token expires in less than 2 hours
            $token = $user->currentAccessToken();
            if ($token && $token->expires_at) {
                $hoursUntilExpiry = now()->diffInHours($token->expires_at);
                
                if ($hoursUntilExpiry < 2) {
                    $newToken = $user->refreshAuthToken();
                    
                    if ($newToken) {
                        return response()->json([
                            'message' => 'Token refreshed',
                            'token' => $newToken->plainTextToken,
                            'user' => $user
                        ], 200);
                    }
                }
            }
        }
        
        return $next($request);
    }
}
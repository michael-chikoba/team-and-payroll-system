<?php

namespace App\Traits;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;

trait ManagesTokens
{
    public function createAuthToken(string $name = 'auth-token', array $abilities = ['*'])
    {
        // Revoke all existing tokens for this user
        $this->tokens()->delete();
        
        // Create new token with expiration
        // Cast to int to avoid type error
        $expirationMinutes = (int) config('sanctum.expiration', 1440);
        $expiresAt = now()->addMinutes($expirationMinutes);
        
        $token = $this->createToken($name, $abilities, $expiresAt);
        
        // Store session data
        $this->createSession($token->accessToken);
        
        return $token;
    }
    
    public function refreshAuthToken()
    {
        $currentToken = $this->currentAccessToken();
        
        if (!$currentToken) {
            return null;
        }
        
        // Create new token
        $newToken = $this->createAuthToken('auth-token');
        
        // Delete old token
        $currentToken->delete();
        
        return $newToken;
    }
    
    protected function createSession($token)
    {
        DB::table('sessions')->updateOrInsert(
            ['user_id' => $this->id],
            [
                'id' => $token->token,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'payload' => base64_encode(json_encode([
                    'user_id' => $this->id,
                    'token_id' => $token->id,
                    'created_at' => now()->toDateTimeString(),
                ])),
                'last_activity' => now()->timestamp,
            ]
        );
    }
    
    public function revokeAllTokens()
    {
        $this->tokens()->delete();
        DB::table('sessions')->where('user_id', $this->id)->delete();
    }
    
    public function isTokenExpired()
    {
        $token = $this->currentAccessToken();
        
        if (!$token || !$token->expires_at) {
            return false;
        }
        
        return $token->expires_at->isPast();
    }
}
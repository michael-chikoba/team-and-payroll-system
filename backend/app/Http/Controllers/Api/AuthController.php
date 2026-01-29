<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\LoginAudit;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}
    
    public function register(RegisterRequest $request): JsonResponse
    {
        $ip = $this->locationService->getRealIp();
        
        Log::info('User registration attempt', [
            'email' => $request->email,
            'role' => $request->role,
            'ip' => $ip,
        ]);

        try {
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ];

            $user = User::create($userData);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $tokenResult = $user->createAuthToken('auth-token');
            
            $loginAudit = $this->logLoginAttempt(
                $user->id,
                $user->email,
                $ip,
                $request->userAgent(),
                'success'
            );

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $tokenResult->plainTextToken,
                'expires_at' => $tokenResult->accessToken->expires_at->toISOString(),
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $ip = $this->locationService->getRealIp();
        $userAgent = $request->userAgent();
        
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $ip,
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $this->logLoginAttempt(
                    $user?->id,
                    $request->email,
                    $ip,
                    $userAgent,
                    'failed',
                    'Invalid credentials'
                );

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Create token with expiration
            $tokenResult = $user->createAuthToken('auth-token');
            
            $loginAudit = $this->logLoginAttempt(
                $user->id,
                $user->email,
                $ip,
                $userAgent,
                'success'
            );

            Log::info('✅ Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_expires_at' => $tokenResult->accessToken->expires_at,
            ]);

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $tokenResult->plainTextToken,
                'expires_at' => $tokenResult->accessToken->expires_at->toISOString(),
                'message' => 'Login successful',
                'login_info' => $loginAudit ? [
                    'location' => $loginAudit->location,
                    'device' => $loginAudit->device_type,
                    'browser' => $loginAudit->browser,
                ] : null,
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Login failed. Please try again.'],
            ]);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        Log::info('Logout attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        try {
            // Update logout time
            $latestLogin = LoginAudit::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();

            if ($latestLogin) {
                $latestLogin->update(['logout_at' => now()]);
            }

            // Revoke all tokens and sessions
            $user->revokeAllTokens();

            Log::info('✅ Logout successful', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('employee');

        return response()->json([
            'user' => $user->makeHidden('password'),
            'token_expires_at' => $user->currentAccessToken()?->expires_at?->toISOString(),
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            $newToken = $user->refreshAuthToken();
            
            if (!$newToken) {
                return response()->json([
                    'message' => 'Failed to refresh token'
                ], 500);
            }
            
            Log::info('Token refreshed', [
                'user_id' => $user->id,
                'expires_at' => $newToken->accessToken->expires_at,
            ]);
            
            return response()->json([
                'token' => $newToken->plainTextToken,
                'expires_at' => $newToken->accessToken->expires_at->toISOString(),
                'user' => $user->makeHidden('password'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    private function logLoginAttempt(
        ?int $userId,
        string $email,
        string $ip,
        ?string $userAgent,
        string $status,
        ?string $failureReason = null
    ): ?LoginAudit {
        try {
            $locationData = $this->locationService->getLocationFromIp($ip);
            $deviceInfo = $this->locationService->parseUserAgent($userAgent ?? '');

            $auditData = array_merge([
                'user_id' => $userId,
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status' => $status,
                'failure_reason' => $failureReason,
                'login_at' => now(),
            ], $locationData, $deviceInfo);

            return LoginAudit::create($auditData);

        } catch (\Exception $e) {
            Log::error('Failed to log login audit', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        Log::info('Password reset request', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->validate(['email' => 'required|email']);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                Log::info('Password reset initiated', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                // TODO: Implement actual password reset logic
                // Send email, create reset token, etc.

            } else {
                Log::warning('Password reset request for non-existent email', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);
            }

            return response()->json([
                'message' => 'If the email exists, a password reset link has been sent.'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'If the email exists, a password reset link has been sent.'
            ]);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        Log::info('Password reset attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            // TODO: Implement actual password reset logic
            // Validate token, update password, etc.

            $user = User::where('email', $request->email)->first();

            if ($user) {
                Log::info('Password reset completed successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } else {
                Log::warning('Password reset for non-existent user', [
                    'email' => $request->email,
                ]);
            }

            return response()->json([
                'message' => 'Password has been reset successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Password reset failed. Please try again.'
            ], 500);
        }
    }
    public function debugToken(Request $request): JsonResponse
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json([
            'authenticated' => false,
            'message' => 'No user found'
        ]);
    }
    
    $token = $user->currentAccessToken();
    
    $sessionData = DB::table('sessions')
        ->where('user_id', $user->id)
        ->first();
    
    return response()->json([
        'authenticated' => true,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ],
        'token' => [
            'id' => $token?->id,
            'name' => $token?->name,
            'abilities' => $token?->abilities,
            'expires_at' => $token?->expires_at?->toISOString(),
            'created_at' => $token?->created_at?->toISOString(),
            'is_expired' => $token?->expires_at ? $token->expires_at->isPast() : null,
        ],
        'session' => $sessionData ? [
            'id' => $sessionData->id,
            'ip_address' => $sessionData->ip_address,
            'last_activity' => date('Y-m-d H:i:s', $sessionData->last_activity),
        ] : null,
        'timestamp' => now()->toISOString(),
    ]);
}

    /**
     * Get the login attempts history for current user (for security monitoring)
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        Log::info('Login history accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        try {
            $limit = $request->get('limit', 20);
            
            $history = LoginAudit::where('user_id', $user->id)
                ->orderBy('login_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($audit) {
                    return [
                        'id' => $audit->id,
                        'login_at' => $audit->login_at,
                        'logout_at' => $audit->logout_at,
                        'ip_address' => $audit->ip_address,
                        'location' => $audit->location,
                        'city' => $audit->city,
                        'country' => $audit->country,
                        'device_type' => $audit->device_type,
                        'browser' => $audit->browser,
                        'platform' => $audit->platform,
                        'status' => $audit->status,
                        'failure_reason' => $audit->failure_reason,
                        'session_duration_minutes' => $audit->session_duration,
                        'is_suspicious' => $audit->isSuspicious(),
                    ];
                });

            $stats = [
                'total_logins' => LoginAudit::where('user_id', $user->id)
                    ->where('status', 'success')
                    ->count(),
                'failed_attempts' => LoginAudit::where('user_id', $user->id)
                    ->where('status', 'failed')
                    ->count(),
                'unique_locations' => LoginAudit::where('user_id', $user->id)
                    ->where('status', 'success')
                    ->distinct('city')
                    ->count('city'),
                'last_login' => LoginAudit::where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('id', '!=', $history->first()?->id)
                    ->latest('login_at')
                    ->first(),
            ];

            return response()->json([
                'success' => true,
                'history' => $history,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve login history', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve login history'
            ], 500);
        }
    }
}
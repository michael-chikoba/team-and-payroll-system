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
use Illuminate\Support\Facades\Auth;
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
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ];

            Log::debug('Attempting to create user with data', [
                'email' => $userData['email'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'role' => $userData['role'],
                'has_password' => !empty($userData['password']),
            ]);

            // Check if we need to add a name field for the database
            $user = new User();
            $fillable = $user->getFillable();
            
            if (in_array('name', $fillable)) {
                $userData['name'] = "{$userData['first_name']} {$userData['last_name']}";
                Log::debug('Added name field for database compatibility', [
                    'name' => $userData['name']
                ]);
            }

            $user = User::create($userData);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            // Log the initial registration login
            $loginAudit = $this->logLoginAttempt(
                $user->id,
                $user->email,
                $ip,
                $request->userAgent(),
                'success'
            );

            Log::info('Auth token created for user', [
                'user_id' => $user->id,
                'token_type' => 'auth-token',
                'location' => $loginAudit ? $loginAudit->location : 'unknown',
            ]);

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'message' => 'User registered successfully',
                'login_location' => $loginAudit ? $loginAudit->location : null,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during user registration', [
                'email' => $request->email,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'driver_code' => $e->errorInfo[1] ?? 'unknown',
            ]);

            if (str_contains($e->getMessage(), "Field 'name' doesn't have a default value")) {
                Log::error('Database schema issue: name field required but not provided', [
                    'email' => $request->email,
                    'solution' => 'Update User model fillable fields or database schema',
                ]);
                
                return response()->json([
                    'message' => 'Registration failed due to system configuration. Please contact administrator.'
                ], 500);
            }

            return response()->json([
                'message' => 'Registration failed due to database error.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('User registration failed with general exception', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
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
            'user_agent' => $userAgent,
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Login failed - user not found', [
                    'email' => $request->email,
                    'ip' => $ip,
                ]);

                // Log failed attempt
                $this->logLoginAttempt(
                    null,
                    $request->email,
                    $ip,
                    $userAgent,
                    'failed',
                    'User not found'
                );

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Login failed - invalid password', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $ip,
                ]);

                // Log failed attempt
                $this->logLoginAttempt(
                    $user->id,
                    $user->email,
                    $ip,
                    $userAgent,
                    'failed',
                    'Invalid password'
                );

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if user is active or has any restrictions
            if (property_exists($user, 'status') && $user->status !== 'active') {
                Log::warning('Login failed - user account not active', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $user->status,
                ]);

                // Log failed attempt
                $this->logLoginAttempt(
                    $user->id,
                    $user->email,
                    $ip,
                    $userAgent,
                    'failed',
                    'Account inactive'
                );

                throw ValidationException::withMessages([
                    'email' => ['Your account is not active. Please contact administrator.'],
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            // Log successful login with location
            $loginAudit = $this->logLoginAttempt(
                $user->id,
                $user->email,
                $ip,
                $userAgent,
                'success'
            );

            // Check for suspicious login
            if ($loginAudit && $loginAudit->isSuspicious()) {
                Log::warning('⚠️ SUSPICIOUS LOGIN DETECTED', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'location' => $loginAudit->location,
                    'ip' => $ip,
                    'device' => $loginAudit->device_type,
                    'browser' => $loginAudit->browser,
                ]);

                // TODO: Send security alert email to user
                // $user->notify(new SuspiciousLoginNotification($loginAudit));
            }

            Log::info('✅ Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'token_created' => true,
                'location' => $loginAudit ? $loginAudit->location : 'unknown',
                'device' => $loginAudit ? $loginAudit->device_type : 'unknown',
                'browser' => $loginAudit ? $loginAudit->browser : 'unknown',
            ]);

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'message' => 'Login successful',
                'login_info' => $loginAudit ? [
                    'location' => $loginAudit->location,
                    'device' => $loginAudit->device_type,
                    'browser' => $loginAudit->browser,
                    'platform' => $loginAudit->platform,
                    'is_suspicious' => $loginAudit->isSuspicious(),
                ] : null,
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login process failed with exception', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
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
            'ip' => $request->ip(),
        ]);

        try {
            $tokenId = $request->user()->currentAccessToken()->id;
            
            // Update logout time for latest login audit
            $latestLogin = LoginAudit::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();

            if ($latestLogin) {
                $latestLogin->update([
                    'logout_at' => now()
                ]);

                Log::info('✅ Logout successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token_id' => $tokenId,
                    'session_duration_minutes' => $latestLogin->session_duration,
                    'login_at' => $latestLogin->login_at,
                    'logout_at' => $latestLogin->logout_at,
                ]);
            } else {
                Log::info('✅ Logout successful (no active session found)', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'token_id' => $tokenId,
                ]);
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully',
                'session_duration' => $latestLogin ? $latestLogin->session_duration : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id,
                'email' => $user->email,
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
        
        Log::debug('User profile request', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        try {
            $user->load('employee');

            Log::debug('User profile loaded successfully', [
                'user_id' => $user->id,
                'employee_loaded' => !is_null($user->employee),
            ]);

            return response()->json([
                'user' => $user->makeHidden('password')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'user' => $user->makeHidden('password'),
                'message' => 'Profile loaded but employee data unavailable'
            ]);
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

    /**
     * Log login attempt with location data
     */
    private function logLoginAttempt(
        ?int $userId,
        string $email,
        string $ip,
        ?string $userAgent,
        string $status,
        ?string $failureReason = null
    ): ?LoginAudit {
        try {
            // Get location data
            $locationData = $this->locationService->getLocationFromIp($ip);
            
            // Parse user agent
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
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            
            return null; // Don't fail the login if audit logging fails
        }
    }
}
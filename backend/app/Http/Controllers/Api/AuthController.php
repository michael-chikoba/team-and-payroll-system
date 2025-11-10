<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
      public function register(RegisterRequest $request): JsonResponse
    {
        Log::info('User registration attempt', [
            'email' => $request->email,
            'role' => $request->role,
            'ip' => $request->ip(),
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

            Log::info('Auth token created for user', [
                'user_id' => $user->id,
                'token_type' => 'auth-token',
            ]);

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'message' => 'User registered successfully'
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during user registration', [
                'email' => $request->email,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'driver_code' => $e->errorInfo[1] ?? 'unknown',
            ]);

            // Check for specific database errors
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
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('Login failed - user not found', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Login failed - invalid password', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

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

                throw ValidationException::withMessages([
                    'email' => ['Your account is not active. Please contact administrator.'],
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'token_created' => true,
            ]);

            return response()->json([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'message' => 'Login successful'
            ]);

        } catch (ValidationException $e) {
            // Re-throw validation exceptions so they're handled properly
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
            
            $request->user()->currentAccessToken()->delete();

            Log::info('Logout successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_id' => $tokenId,
            ]);

            return response()->json([
                'message' => 'Logged out successfully'
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
                // Log that we found the user (but don't reset password yet)
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

            // Always return the same message for security
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
     * Get the login attempts history for a user (for security monitoring)
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        Log::info('Login history accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // TODO: Implement login history retrieval
        // This would typically come from a separate login_attempts table

        return response()->json([
            'message' => 'Login history feature not implemented yet'
        ]);
    }
}
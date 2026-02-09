<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuperAdminUserController extends Controller
{
    /**
     * Get all users in the system
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['currentBusiness', 'businesses']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            }

            // Role filter
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // SuperAdmin filter
            if ($request->filled('is_superadmin')) {
                $query->where('is_superadmin', $request->boolean('is_superadmin'));
            }

            // Business filter
            if ($request->filled('business_id')) {
                $query->whereHas('businesses', function ($q) use ($request) {
                    $q->where('businesses.id', $request->business_id);
                });
            }

            // Sorting
            $sortBy = $request->get('sortBy', 'created_at');
            $sortOrder = $request->get('sortOrder', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $users = $query->paginate($perPage);

            // Calculate stats
            $stats = [
                'total_users' => User::count(),
                'superadmins' => User::where('is_superadmin', true)->count(),
                'admins' => User::where('role', 'admin')->count(),
                'managers' => User::where('role', 'manager')->count(),
                'employees' => User::where('role', 'employee')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'from' => $users->firstItem(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'to' => $users->lastItem(),
                    'total' => $users->total(),
                ],
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Error fetching users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:employee,manager,admin',
            'password' => 'nullable|string|min:8',
            'is_superadmin' => 'nullable|boolean',
            'country_id' => 'nullable|exists:countries,id',
            'current_business_id' => 'nullable|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'password' => Hash::make($request->password ?? Str::random(16)),
                'is_superadmin' => $request->boolean('is_superadmin', false),
                'country_id' => $request->country_id,
                'current_business_id' => $request->current_business_id,
                'email_verified_at' => $request->boolean('email_verified', false) ? now() : null,
            ]);

            DB::commit();

            $user->load(['currentBusiness', 'businesses']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: User creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Show user details
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load([
                'currentBusiness',
                'businesses.country',
                'employee'
            ]);

            // Get user's activity statistics
            $stats = [
                'businesses_count' => $user->businesses()->count(),
                'primary_businesses' => $user->businesses()->wherePivot('is_primary', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $stats,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Error fetching user details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:50',
            'role' => 'sometimes|required|in:employee,manager,admin',
            'is_superadmin' => 'sometimes|boolean',
            'country_id' => 'sometimes|nullable|exists:countries,id',
            'current_business_id' => 'sometimes|nullable|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user->update($request->only([
                'first_name',
                'last_name',
                'email',
                'phone',
                'role',
                'is_superadmin',
                'country_id',
                'current_business_id',
            ]));

            DB::commit();

            $user->load(['currentBusiness', 'businesses']);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: User update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Toggle SuperAdmin status
     */
    public function toggleSuperAdmin(Request $request, User $user): JsonResponse
    {
        try {
            // Prevent removing your own superadmin status
            if ($user->id === $request->user()->id && $user->is_superadmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot remove your own superadmin status'
                ], 422);
            }

            $newStatus = !$user->is_superadmin;
            $user->update(['is_superadmin' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus 
                    ? 'User granted superadmin privileges' 
                    : 'Superadmin privileges removed',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Toggle superadmin failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update superadmin status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Password reset failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|string|in:DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Prevent deleting yourself
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 422);
            }

            DB::beginTransaction();

            $userName = $user->first_name . ' ' . $user->last_name;

            // Detach from all businesses
            $user->businesses()->detach();

            // Delete the user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "User '{$userName}' has been permanently deleted"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: User deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
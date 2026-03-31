<?php

namespace App\Http\Controllers\Api\Business;

use App\Models\Business;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class BusinessController extends Controller
{
    /**
     * Check if user has access to business
     */
    private function checkBusinessAccess(User $user, Business $business): bool
    {
        return $user->businesses()->where('businesses.id', $business->id)->exists();
    }

    /**
     * Check if user is admin of business
     */
    private function checkBusinessAdmin(User $user, Business $business): bool
    {
        return $business->admins()
            ->where('user_id', $user->id)
            ->whereIn('business_admins.role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Check if user is owner of business
     */
    private function checkBusinessOwner(User $user, Business $business): bool
    {
        return $business->admins()
            ->where('user_id', $user->id)
            ->where('business_admins.role', 'owner')
            ->exists();
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $businesses = $user->businesses()
            ->with(['country', 'admins'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $businesses,
            'current_business_id' => $user->current_business_id
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'legal_name'                => 'required|string|max:255|unique:businesses,legal_name',
            'registration_number'       => 'nullable|string|unique:businesses,registration_number',
            'tax_identification_number' => 'nullable|string',
            'business_type'             => 'required|in:sole_proprietorship,partnership,corporation,llc',
            'industry'                  => 'nullable|string|max:255',
            'website'                   => 'nullable|url|max:255',
            'email'                     => 'required|email|max:255',
            'phone'                     => 'nullable|string|max:50',
            'address_line_1'            => 'required|string|max:255',
            'address_line_2'            => 'nullable|string|max:255',
            'city'                      => 'required|string|max:100',
            'state'                     => 'required|string|max:100',
            'postal_code'               => 'required|string|max:20',
            'country_id'                => 'required|exists:countries,id',
            'currency_code'             => 'required|string|size:3',
            'pay_period'                => 'required|in:weekly,bi-weekly,semi-monthly,monthly',
            'admin_first_name'          => 'required|string|max:255',
            'admin_last_name'           => 'required|string|max:255',
            'admin_email'               => 'required|email|max:255',
            'admin_phone'               => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $business = Business::create([
                'name'                      => $request->name,
                'legal_name'                => $request->legal_name,
                'registration_number'       => $request->registration_number,
                'tax_identification_number' => $request->tax_identification_number,
                'business_type'             => $request->business_type,
                'industry'                  => $request->industry,
                'website'                   => $request->website,
                'email'                     => $request->email,
                'phone'                     => $request->phone,
                'address_line_1'            => $request->address_line_1,
                'address_line_2'            => $request->address_line_2,
                'city'                      => $request->city,
                'state'                     => $request->state,
                'postal_code'               => $request->postal_code,
                'country_id'                => $request->country_id,
                'currency_code'             => $request->currency_code,
                'pay_period'                => $request->pay_period,
                'fiscal_year_start'         => '2024-01-01',
                'status'                    => 'active',
                'employee_limit'            => 50,
                'current_employee_count'    => 0,
            ]);

            $adminUser = $this->findOrCreateAdminUser($request);

            $business->admins()->attach($adminUser->id, [
                'role'       => 'owner',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $currentUser = $request->user();
            if ($currentUser->id !== $adminUser->id) {
                $business->admins()->attach($currentUser->id, [
                    'role'       => 'admin',
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $currentUser->update(['current_business_id' => $business->id]);

            if (!$adminUser->country_id && $request->country_id) {
                $adminUser->update(['country_id' => $request->country_id]);
            }

            DB::commit();

            $business->load(['country', 'admins']);

            return response()->json([
                'success' => true,
                'message' => 'Business registered successfully',
                'data'    => $business
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Business creation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to register business',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function findOrCreateAdminUser(Request $request): User
    {
        $currentUser = $request->user();

        if ($currentUser->email === $request->admin_email) {
            return $currentUser;
        }

        $existingUser = User::where('email', $request->admin_email)->first();

        if ($existingUser) {
            return $existingUser;
        }

        return User::create([
            'first_name'        => $request->admin_first_name,
            'last_name'         => $request->admin_last_name,
            'email'             => $request->admin_email,
            'phone'             => $request->admin_phone,
            'password'          => bcrypt(Str::random(12)),
            'role'              => 'admin',
            'country_id'        => $request->country_id,
            'email_verified_at' => null,
        ]);
    }

    public function show(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAccess($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this business'
            ], 403);
        }

        $business->load([
            'country',
            'admins',
            'employees',
            'employees.country'
        ]);

        return response()->json([
            'success' => true,
            'data'    => $business
        ]);
    }

    public function update(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAdmin($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this business'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                      => 'sometimes|required|string|max:255',
            'legal_name'                => 'sometimes|required|string|max:255|unique:businesses,legal_name,' . $business->id,
            'registration_number'       => 'sometimes|nullable|string|unique:businesses,registration_number,' . $business->id,
            'tax_identification_number' => 'sometimes|nullable|string',
            'business_type'             => 'sometimes|required|in:sole_proprietorship,partnership,corporation,llc',
            'industry'                  => 'sometimes|nullable|string|max:255',
            'website'                   => 'sometimes|nullable|url|max:255',
            'email'                     => 'sometimes|required|email|max:255',
            'phone'                     => 'sometimes|nullable|string|max:50',
            'address_line_1'            => 'sometimes|required|string|max:255',
            'address_line_2'            => 'sometimes|nullable|string|max:255',
            'city'                      => 'sometimes|required|string|max:100',
            'state'                     => 'sometimes|required|string|max:100',
            'postal_code'               => 'sometimes|required|string|max:20',
            'country_id'                => 'sometimes|required|exists:countries,id',
            'currency_code'             => 'sometimes|required|string|size:3',
            'pay_period'                => 'sometimes|required|in:weekly,bi-weekly,semi-monthly,monthly',
            'status'                    => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $business->update($request->only([
                'name', 'legal_name', 'registration_number', 'tax_identification_number',
                'business_type', 'industry', 'website', 'email', 'phone',
                'address_line_1', 'address_line_2', 'city', 'state', 'postal_code',
                'country_id', 'currency_code', 'pay_period', 'status',
            ]));

            DB::commit();

            $business->load(['country', 'admins']);

            return response()->json([
                'success' => true,
                'message' => 'Business updated successfully',
                'data'    => $business
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Business update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update business',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(Request $request, Business $business): JsonResponse
    {
        Log::info('Delete request received', [
            'user_id'     => auth()->id(),
            'business_id' => $business->id,
        ]);

        $user = $request->user();

        if (!$this->checkBusinessOwner($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Only business owners can delete the business'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $isPrimaryAdmin = $business->admins()
                ->where('user_id', $user->id)
                ->where('is_primary', true)
                ->exists();

            if (!$isPrimaryAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the primary administrator can delete the business'
                ], 403);
            }

            // No status column on employees — block if any employees exist at all
            $employeeCount = $business->employees()->count();
            if ($employeeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete business with existing employees. Please remove all employees first.'
                ], 422);
            }

            // Block if payroll history exists
            if ($business->payrolls()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete business with existing payroll records.'
                ], 422);
            }

            // Detach pivot relationships
            $business->admins()->detach();
            $business->businessGroups()->detach();

            // Clear current_business_id for ALL users pointing to this business
            User::where('current_business_id', $business->id)
                ->update(['current_business_id' => null]);

            $business->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Business deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Business deletion failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete business',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function switchBusiness(Request $request, Business $business): JsonResponse
    {
        try {
            Log::info('Business switch request received', [
                'user_id'       => $request->user()->id,
                'business_id'   => $business->id,
                'business_name' => $business->name
            ]);

            $user = $request->user();

            $hasAccess = $user->businesses()->where('businesses.id', $business->id)->exists();

            Log::info('Access check result', [
                'user_id'     => $user->id,
                'business_id' => $business->id,
                'has_access'  => $hasAccess
            ]);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to access this business'
                ], 403);
            }

            try {
                $user->current_business_id = $business->id;
                $user->save();

                Log::info('User current_business_id updated', [
                    'user_id'         => $user->id,
                    'new_business_id' => $business->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update user current_business_id', [
                    'error'       => $e->getMessage(),
                    'user_id'     => $user->id,
                    'business_id' => $business->id
                ]);
                throw $e;
            }

            $business->load(['country', 'admins']);

            // No status column on employees — use plain count for both
            $stats = [
                'total_employees'  => 0,
                'active_employees' => 0,
                'total_admins'     => $business->admins()->count(),
            ];

            try {
                $count                     = $business->employees()->count();
                $stats['total_employees']  = $count;
                $stats['active_employees'] = $count;
            } catch (\Exception $e) {
                Log::warning('Could not fetch employee stats', [
                    'error' => $e->getMessage()
                ]);
            }

            $user->refresh();

            Log::info('Business switch successful', [
                'user_id'       => $user->id,
                'business_id'   => $business->id,
                'business_name' => $business->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Business switched successfully',
                'data'    => [
                    'business' => $business,
                    'stats'    => $stats,
                    'user'     => [
                        'id'                  => $user->id,
                        'current_business_id' => $user->current_business_id,
                        'email'               => $user->email,
                        'first_name'          => $user->first_name,
                        'last_name'           => $user->last_name,
                        'role'                => $user->role,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Business switch failed with exception', [
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'user_id'     => $request->user()->id ?? 'unknown',
                'business_id' => $business->id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to switch business',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getStats(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAccess($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view business statistics'
            ], 403);
        }

        try {
            $stats = [
                'total_employees'  => 0,
                'active_employees' => 0,
                'total_admins'     => $business->admins()->count(),
                'departments'      => [],
            ];

            try {
                $count                     = $business->employees()->count();
                $stats['total_employees']  = $count;
                $stats['active_employees'] = $count;
                $stats['departments']      = $business->employees()
                                                ->distinct()
                                                ->pluck('department')
                                                ->filter()
                                                ->values();
            } catch (\Exception $e) {
                Log::warning('Could not fetch employee stats', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get business stats', [
                'error'       => $e->getMessage(),
                'business_id' => $business->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch business statistics',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getCurrentBusiness(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->current_business_id) {
            return response()->json([
                'success' => false,
                'message' => 'No current business set'
            ], 404);
        }

        $business = Business::with(['country', 'admins'])
            ->find($user->current_business_id);

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Current business not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $business
        ]);
    }

    public function getCountries(): JsonResponse
    {
        $countries = Country::where('is_active', true)
            ->get(['id', 'name', 'code', 'currency_code', 'currency_symbol']);

        return response()->json([
            'success' => true,
            'data'    => $countries
        ]);
    }

    public function addAdmin(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAdmin($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage administrators'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id'    => 'required|exists:users,id',
            'role'       => 'required|string|in:admin,manager,owner',
            'is_primary' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->is_primary) {
                $business->admins()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            if ($business->admins()->where('user_id', $request->user_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already an admin for this business'
                ], 422);
            }

            $business->admins()->attach($request->user_id, [
                'role'       => $request->role,
                'is_primary' => $request->is_primary ?? false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            $business->load('admins');

            return response()->json([
                'success' => true,
                'message' => 'Admin added successfully',
                'data'    => $business
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add admin',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function removeAdmin(Request $request, Business $business, User $adminUser): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAdmin($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage administrators'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $admin = $business->admins()->where('user_id', $adminUser->id)->first();

            if ($admin && $admin->pivot->is_primary && $business->admins()->wherePivot('is_primary', true)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the only primary admin'
                ], 422);
            }

            $business->admins()->detach($adminUser->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Admin removed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove admin',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updatePrimaryAdmin(Request $request, Business $business, User $adminUser): JsonResponse
    {
        $user = $request->user();

        if (!$this->checkBusinessAdmin($user, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage administrators'
            ], 403);
        }

        try {
            DB::beginTransaction();

            if (!$business->admins()->where('user_id', $adminUser->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not an administrator of this business'
                ], 422);
            }

            $business->admins()
                ->wherePivot('is_primary', true)
                ->update(['is_primary' => false]);

            $business->admins()
                ->where('user_id', $adminUser->id)
                ->update([
                    'is_primary' => true,
                    'role'       => 'owner',
                    'updated_at' => now()
                ]);

            DB::commit();

            $business->load('admins');

            return response()->json([
                'success' => true,
                'message' => 'Primary administrator updated successfully',
                'data'    => $business
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update primary administrator',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
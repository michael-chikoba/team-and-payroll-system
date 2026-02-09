<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessActivityLog;
use App\Models\BusinessLimitHistory;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuperAdminBusinessController extends Controller
{
    /**
     * Get all businesses in the system (paginated with filters)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Business::with(['country', 'admins', 'createdByAdmin', 'suspendedByAdmin']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Subscription tier filter
            if ($request->filled('subscription_tier')) {
                $query->where('subscription_tier', $request->subscription_tier);
            }

            // Trial filter
            if ($request->filled('is_trial')) {
                $query->where('is_trial', $request->boolean('is_trial'));
            }

            // Country filter
            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            // Active subscription filter
            if ($request->filled('has_active_subscription')) {
                if ($request->boolean('has_active_subscription')) {
                    $query->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('is_trial', true)
                                ->where('trial_end_date', '>=', now());
                        })->orWhere(function ($subQ) {
                            $subQ->where('is_trial', false)
                                ->where('subscription_end_date', '>=', now());
                        });
                    });
                } else {
                    $query->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('is_trial', true)
                                ->where('trial_end_date', '<', now());
                        })->orWhere(function ($subQ) {
                            $subQ->where('is_trial', false)
                                ->where('subscription_end_date', '<', now());
                        });
                    });
                }
            }

            // Suspended businesses
            if ($request->filled('is_suspended')) {
                if ($request->boolean('is_suspended')) {
                    $query->whereNotNull('suspended_at');
                } else {
                    $query->whereNull('suspended_at');
                }
            }

            // At limit filter
            if ($request->filled('at_limit')) {
                if ($request->boolean('at_limit')) {
                    $query->whereColumn('current_employee_count', '>=', 'employee_limit');
                }
            }

            // Sorting
            $sortBy = $request->get('sortBy', 'created_at');
            $sortOrder = $request->get('sortOrder', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $businesses = $query->paginate($perPage);

            // Calculate system-wide stats
            $stats = [
                'total_businesses' => Business::count(),
                'active_businesses' => Business::where('status', 'active')->whereNull('suspended_at')->count(),
                'suspended_businesses' => Business::whereNotNull('suspended_at')->count(),
                'trial_businesses' => Business::where('is_trial', true)->count(),
                'total_employees' => Business::sum('current_employee_count'),
                'total_employee_limit' => Business::sum('employee_limit'),
                'subscription_tiers' => Business::select('subscription_tier', DB::raw('count(*) as count'))
                    ->groupBy('subscription_tier')
                    ->get()
                    ->pluck('count', 'subscription_tier'),
            ];

            return response()->json([
                'success' => true,
                'data' => $businesses->items(),
                'meta' => [
                    'current_page' => $businesses->currentPage(),
                    'from' => $businesses->firstItem(),
                    'last_page' => $businesses->lastPage(),
                    'per_page' => $businesses->perPage(),
                    'to' => $businesses->lastItem(),
                    'total' => $businesses->total(),
                ],
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Error fetching businesses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch businesses',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a business on behalf of another user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'legal_name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|unique:businesses',
            'tax_identification_number' => 'nullable|string',
            'business_type' => 'required|in:sole_proprietorship,partnership,corporation,llc',
            'industry' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country_id' => 'required|exists:countries,id',
            'currency_code' => 'required|string|size:3',
            'pay_period' => 'required|in:weekly,bi-weekly,semi-monthly,monthly',
            
            // Owner information
            'owner_first_name' => 'required|string|max:255',
            'owner_last_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'owner_phone' => 'nullable|string|max:50',
            
            // Subscription settings
            'employee_limit' => 'nullable|integer|min:1|max:10000',
            'subscription_tier' => 'nullable|in:basic,standard,premium,enterprise',
            'is_trial' => 'nullable|boolean',
            'trial_days' => 'nullable|integer|min:1|max:365',
            'subscription_months' => 'nullable|integer|min:1|max:60',
            'features' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'admin_notes' => 'nullable|string',
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

            // Calculate subscription dates
            $subscriptionStartDate = now();
            $isTrial = $request->get('is_trial', false);
            $trialEndDate = null;
            $subscriptionEndDate = null;

            if ($isTrial) {
                $trialDays = $request->get('trial_days', 30);
                $trialEndDate = $subscriptionStartDate->copy()->addDays($trialDays);
            } else {
                $subscriptionMonths = $request->get('subscription_months', 12);
                $subscriptionEndDate = $subscriptionStartDate->copy()->addMonths($subscriptionMonths);
            }

            // Create business
            $business = Business::create([
                'name' => $request->name,
                'legal_name' => $request->legal_name,
                'registration_number' => $request->registration_number,
                'tax_identification_number' => $request->tax_identification_number,
                'business_type' => $request->business_type,
                'industry' => $request->industry,
                'website' => $request->website,
                'email' => $request->email,
                'phone' => $request->phone,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country_id' => $request->country_id,
                'currency_code' => $request->currency_code,
                'pay_period' => $request->pay_period,
                'fiscal_year_start' => now()->startOfYear(),
                'status' => 'active',
                'employee_limit' => $request->get('employee_limit', 50),
                'current_employee_count' => 0,
                'subscription_tier' => $request->get('subscription_tier', 'basic'),
                'subscription_start_date' => $subscriptionStartDate,
                'subscription_end_date' => $subscriptionEndDate,
                'is_trial' => $isTrial,
                'trial_end_date' => $trialEndDate,
                'features' => $request->get('features', []),
                'restrictions' => $request->get('restrictions', []),
                'admin_notes' => $request->admin_notes,
                'created_by_admin_id' => $request->user()->id,
                'last_active_at' => now(),
            ]);

            // Find or create owner
            $owner = User::where('email', $request->owner_email)->first();

            if (!$owner) {
                $owner = User::create([
                    'first_name' => $request->owner_first_name,
                    'last_name' => $request->owner_last_name,
                    'email' => $request->owner_email,
                    'phone' => $request->owner_phone,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'admin',
                    'country_id' => $request->country_id,
                    'email_verified_at' => null,
                ]);
            }

            // Attach owner as primary admin
            $business->admins()->attach($owner->id, [
                'role' => 'owner',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log activity
            BusinessActivityLog::logActivity(
                $business->id,
                $request->user()->id,
                'created',
                "Business created by superadmin for owner: {$owner->email}",
                null,
                $business->toArray()
            );

            DB::commit();

            $business->load(['country', 'admins', 'createdByAdmin']);

            return response()->json([
                'success' => true,
                'message' => 'Business created successfully',
                'data' => $business
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: Business creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create business',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update business settings
     */
    public function update(Request $request, Business $business): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'legal_name' => 'sometimes|required|string|max:255',
            'registration_number' => 'sometimes|nullable|string|unique:businesses,registration_number,' . $business->id,
            'status' => 'sometimes|in:active,inactive,suspended',
            'employee_limit' => 'sometimes|integer|min:1|max:10000',
            'subscription_tier' => 'sometimes|in:basic,standard,premium,enterprise',
            'subscription_end_date' => 'sometimes|nullable|date',
            'trial_end_date' => 'sometimes|nullable|date',
            'features' => 'sometimes|array',
            'restrictions' => 'sometimes|array',
            'admin_notes' => 'sometimes|nullable|string',
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

            $oldValues = $business->toArray();
            $changes = [];

            // Track employee limit changes separately
            if ($request->filled('employee_limit') && $request->employee_limit != $business->employee_limit) {
                $business->updateEmployeeLimit(
                    $request->employee_limit,
                    $request->user()->id,
                    $request->get('limit_change_reason', 'Updated by superadmin')
                );
                $changes['employee_limit'] = [
                    'old' => $oldValues['employee_limit'],
                    'new' => $request->employee_limit
                ];
            }

            // Update other fields
            $updateData = $request->except(['employee_limit', 'limit_change_reason']);
            $business->update($updateData);

            // Log activity
            BusinessActivityLog::logActivity(
                $business->id,
                $request->user()->id,
                'updated',
                'Business updated by superadmin',
                $oldValues,
                array_merge($business->fresh()->toArray(), $changes)
            );

            DB::commit();

            $business->load(['country', 'admins', 'createdByAdmin', 'suspendedByAdmin']);

            return response()->json([
                'success' => true,
                'message' => 'Business updated successfully',
                'data' => $business
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: Business update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Suspend a business
     */
    public function suspend(Request $request, Business $business): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($business->suspended_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business is already suspended'
                ], 422);
            }

            $business->suspend($request->user()->id, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Business suspended successfully',
                'data' => $business->fresh(['country', 'admins', 'suspendedByAdmin'])
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Business suspension failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend business',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Activate a business
     */
    public function activate(Request $request, Business $business): JsonResponse
    {
        try {
            if (!$business->suspended_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business is not suspended'
                ], 422);
            }

            $business->activate($request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Business activated successfully',
                'data' => $business->fresh(['country', 'admins', 'createdByAdmin'])
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Business activation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate business',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update employee limit
     */
    public function updateEmployeeLimit(Request $request, Business $business): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_limit' => 'required|integer|min:1|max:10000',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->employee_limit < $business->current_employee_count) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot set limit below current employee count ({$business->current_employee_count})"
                ], 422);
            }

            $business->updateEmployeeLimit(
                $request->employee_limit,
                $request->user()->id,
                $request->get('reason', 'Limit updated by superadmin')
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee limit updated successfully',
                'data' => [
                    'business' => $business->fresh(),
                    'limit_history' => $business->limitHistory()->latest()->first()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Employee limit update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee limit',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get business activity logs
     */
    public function getActivityLogs(Request $request, Business $business): JsonResponse
    {
        try {
            $logs = $business->activityLogs()
                ->with('admin')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch activity logs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity logs',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get business limit history
     */
    public function getLimitHistory(Request $request, Business $business): JsonResponse
    {
        try {
            $history = $business->limitHistory()
                ->with('changedByAdmin')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $history->items(),
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch limit history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch limit history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system-wide dashboard stats
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $stats = [
                'businesses' => [
                    'total' => Business::count(),
                    'active' => Business::where('status', 'active')->whereNull('suspended_at')->count(),
                    'suspended' => Business::whereNotNull('suspended_at')->count(),
                    'trial' => Business::where('is_trial', true)->where('trial_end_date', '>=', now())->count(),
                ],
                'subscriptions' => [
                    'active' => Business::where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('is_trial', true)->where('trial_end_date', '>=', now());
                        })->orWhere(function ($subQ) {
                            $subQ->where('is_trial', false)->where('subscription_end_date', '>=', now());
                        });
                    })->count(),
                    'expired' => Business::where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('is_trial', true)->where('trial_end_date', '<', now());
                        })->orWhere(function ($subQ) {
                            $subQ->where('is_trial', false)->where('subscription_end_date', '<', now());
                        });
                    })->count(),
                    'by_tier' => Business::select('subscription_tier', DB::raw('count(*) as count'))
                        ->groupBy('subscription_tier')
                        ->get()
                        ->pluck('count', 'subscription_tier'),
                ],
                'employees' => [
                    'total' => Business::sum('current_employee_count'),
                    'total_limit' => Business::sum('employee_limit'),
                    'average_usage' => Business::avg('current_employee_count'),
                ],
                'recent_activity' => BusinessActivityLog::with(['business', 'admin'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch dashboard stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a business (hard delete - use with caution)
     */
    public function destroy(Request $request, Business $business): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|string|in:DELETE',
            'reason' => 'required|string|min:20|max:1000',
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

            // Log the deletion
            BusinessActivityLog::logActivity(
                $business->id,
                $request->user()->id,
                'deleted',
                "Business permanently deleted. Reason: {$request->reason}",
                $business->toArray(),
                null
            );

            // Detach all relationships
            $business->admins()->detach();
            $business->employees()->detach();

            // Delete the business
            $businessName = $business->name;
            $business->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Business '{$businessName}' has been permanently deleted"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SuperAdmin: Business deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete business',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
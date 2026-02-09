<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroup;
use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BusinessGroupController extends Controller
{
    /**
     * Get all business groups the current business belongs to
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business association found'
                ], 404);
            }

            $groups = $business->activeBusinessGroups()
                ->with(['parentGroup', 'createdBy'])
                ->withCount(['businesses', 'tickets', 'tasks'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching business groups', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch business groups'
            ], 500);
        }
    }

    /**
     * Create a new business group
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_type' => 'required|in:holding,franchise,subsidiary,partnership',
            'parent_group_id' => 'nullable|exists:business_groups,id',
            'allow_cross_business_tickets' => 'boolean',
            'allow_cross_business_tasks' => 'boolean',
            'allow_cross_business_projects' => 'boolean',
            'allow_employee_visibility' => 'boolean',
            'allow_resource_sharing' => 'boolean',
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

            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business association found'
                ], 404);
            }

            // Create business group
            $businessGroup = BusinessGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'group_type' => $request->group_type,
                'parent_group_id' => $request->parent_group_id,
                'allow_cross_business_tickets' => $request->get('allow_cross_business_tickets', true),
                'allow_cross_business_tasks' => $request->get('allow_cross_business_tasks', true),
                'allow_cross_business_projects' => $request->get('allow_cross_business_projects', false),
                'allow_employee_visibility' => $request->get('allow_employee_visibility', false),
                'allow_resource_sharing' => $request->get('allow_resource_sharing', false),
                'created_by_user_id' => $user->id,
                'status' => 'active',
            ]);

            // Add creator's business as owner
            $businessGroup->businesses()->attach($business->id, [
                'role' => 'owner',
                'is_primary' => true,
                'can_manage_group' => true,
                'can_invite_businesses' => true,
                'can_view_all_tickets' => true,
                'can_assign_cross_business_tasks' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Log activity
            $businessGroup->logActivity(
                'group_created',
                "Business group '{$businessGroup->name}' created",
                $business->id,
                $user->id
            );

            DB::commit();

            $businessGroup->load(['businesses', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'Business group created successfully',
                'data' => $businessGroup
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating business group', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create business group'
            ], 500);
        }
    }

    /**
     * Show business group details
     */
    public function show(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $businessGroup->load([
                'businesses',
                'parentGroup',
                'childGroups',
                'createdBy'
            ]);

            $businessGroup->loadCount(['businesses', 'tickets', 'tasks']);

            return response()->json([
                'success' => true,
                'data' => $businessGroup
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching business group', [
                'error' => $e->getMessage(),
                'group_id' => $businessGroup->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch business group'
            ], 500);
        }
    }

    /**
     * Update business group
     */
    public function update(Request $request, BusinessGroup $businessGroup): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage this group'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'group_type' => 'sometimes|required|in:holding,franchise,subsidiary,partnership',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessGroup->update($request->only([
                'name',
                'description',
                'group_type',
                'status'
            ]));

            $businessGroup->logActivity(
                'group_updated',
                "Business group '{$businessGroup->name}' updated",
                $business->id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Business group updated successfully',
                'data' => $businessGroup->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating business group', [
                'error' => $e->getMessage(),
                'group_id' => $businessGroup->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business group'
            ], 500);
        }
    }

    /**
     * Update group settings
     */
    public function updateSettings(Request $request, BusinessGroup $businessGroup): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage this group'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'allow_cross_business_tickets' => 'boolean',
            'allow_cross_business_tasks' => 'boolean',
            'allow_cross_business_projects' => 'boolean',
            'allow_employee_visibility' => 'boolean',
            'allow_resource_sharing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessGroup->update($request->only([
                'allow_cross_business_tickets',
                'allow_cross_business_tasks',
                'allow_cross_business_projects',
                'allow_employee_visibility',
                'allow_resource_sharing',
            ]));

            $businessGroup->logActivity(
                'settings_updated',
                "Group settings updated",
                $business->id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $businessGroup->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating group settings', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings'
            ], 500);
        }
    }

    /**
     * Delete business group
     */
    public function destroy(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this group'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $businessGroup->logActivity(
                'group_deleted',
                "Business group '{$businessGroup->name}' deleted",
                $business->id,
                $user->id
            );

            $businessGroup->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Business group deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting business group', [
                'error' => $e->getMessage(),
                'group_id' => $businessGroup->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete business group'
            ], 500);
        }
    }

    /**
     * Get group members
     */
    public function getMembers(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $members = $businessGroup->businesses()
                ->with(['country', 'primaryAdmin'])
                ->withPivot([
                    'role',
                    'is_primary',
                    'can_manage_group',
                    'can_invite_businesses',
                    'can_view_all_tickets',
                    'can_assign_cross_business_tasks',
                    'status',
                    'joined_at'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $members
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group members', [
                'error' => $e->getMessage(),
                'group_id' => $businessGroup->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group members'
            ], 500);
        }
    }

    /**
     * Leave business group
     */
    public function leave(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not a member of this group'
                ], 403);
            }

            // Check if this is the only owner
            $ownerCount = $businessGroup->businesses()
                ->wherePivot('role', 'owner')
                ->wherePivot('status', 'active')
                ->count();

            $isOwner = $businessGroup->businesses()
                ->where('businesses.id', $business->id)
                ->wherePivot('role', 'owner')
                ->exists();

            if ($isOwner && $ownerCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot leave: You are the only owner. Transfer ownership first.'
                ], 422);
            }

            DB::beginTransaction();

            $business->leaveGroup($businessGroup->id);

            $businessGroup->logActivity(
                'business_left',
                "Business '{$business->name}' left the group",
                $business->id,
                $user->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the business group'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error leaving business group', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to leave business group'
            ], 500);
        }
    }

    /**
     * Get group statistics
     */
    public function getStats(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $stats = [
                'total_businesses' => $businessGroup->activeBusinesses()->count(),
                'total_employees' => $businessGroup->allow_employee_visibility 
                    ? $businessGroup->getAllEmployees()->count() 
                    : 0,
                'total_tickets' => $businessGroup->tickets()->count(),
                'total_tasks' => $businessGroup->tasks()->count(),
                'pending_invitations' => $businessGroup->invitations()->pending()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group stats', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Get group activity
     */
    public function getActivity(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $perPage = $request->get('per_page', 20);
            
            $activities = $businessGroup->activityLogs()
                ->with(['user', 'business'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group activity', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity'
            ], 500);
        }
    }

    /**
     * Get all employees across the business group
     */
    public function getGroupEmployees(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if (!$businessGroup->allow_employee_visibility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee visibility is not enabled for this group'
                ], 403);
            }

            $employees = $businessGroup->getAllEmployees();

            return response()->json([
                'success' => true,
                'data' => $employees
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group employees', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group employees'
            ], 500);
        }
    }

    /**
     * Get all users across the business group
     */
    public function getGroupUsers(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $users = $businessGroup->getAllUsers();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group users', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group users'
            ], 500);
        }
    }

    /**
     * Get all businesses in the group
     */
    public function getGroupBusinesses(BusinessGroup $businessGroup, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $businesses = $businessGroup->activeBusinesses()
                ->with(['country'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $businesses
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group businesses', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch businesses'
            ], 500);
        }
    }

    /**
     * Helper method to get current business for user
     */
    private function getCurrentBusiness($user): ?Business
    {
        $employee = $user->employee;
        return $employee ? $employee->business : null;
    }
}
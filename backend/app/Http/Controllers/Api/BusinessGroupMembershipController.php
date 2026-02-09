<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroupMembership;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BusinessGroupMembershipController extends Controller
{
    /**
     * Get all memberships for current business
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

            $memberships = $business->groupMemberships()
                ->with(['businessGroup', 'invitedBy', 'approvedBy'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $memberships
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching memberships', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch memberships'
            ], 500);
        }
    }

    /**
     * Update membership permissions
     */
    public function update(Request $request, BusinessGroupMembership $membership): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$membership->businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage memberships'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|required|in:owner,member,partner',
            'can_manage_group' => 'sometimes|boolean',
            'can_invite_businesses' => 'sometimes|boolean',
            'can_view_all_tickets' => 'sometimes|boolean',
            'can_assign_cross_business_tasks' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $membership->update($request->only([
                'role',
                'can_manage_group',
                'can_invite_businesses',
                'can_view_all_tickets',
                'can_assign_cross_business_tasks',
            ]));

            $membership->businessGroup->logActivity(
                'membership_updated',
                "Membership permissions updated for business '{$membership->business->name}'",
                $business->id,
                $user->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Membership updated successfully',
                'data' => $membership->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating membership', [
                'error' => $e->getMessage(),
                'membership_id' => $membership->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update membership'
            ], 500);
        }
    }

    /**
     * Remove business from group
     */
    public function destroy(BusinessGroupMembership $membership, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$membership->businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to remove members'
            ], 403);
        }

        // Check if removing the only owner
        if ($membership->role === 'owner') {
            $ownerCount = $membership->businessGroup->memberships()
                ->where('role', 'owner')
                ->where('status', 'active')
                ->count();

            if ($ownerCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the only owner. Transfer ownership first.'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $businessName = $membership->business->name;

            $membership->businessGroup->logActivity(
                'membership_removed',
                "Business '{$businessName}' removed from group",
                $business->id,
                $user->id
            );

            $membership->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Business removed from group successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error removing membership', [
                'error' => $e->getMessage(),
                'membership_id' => $membership->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove business'
            ], 500);
        }
    }

    /**
     * Suspend membership
     */
    public function suspend(Request $request, BusinessGroupMembership $membership): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$membership->businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $membership->update(['status' => 'suspended']);

            $membership->businessGroup->logActivity(
                'membership_suspended',
                "Business '{$membership->business->name}' suspended",
                $business->id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Membership suspended successfully',
                'data' => $membership->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error suspending membership', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend membership'
            ], 500);
        }
    }

    /**
     * Activate membership
     */
    public function activate(Request $request, BusinessGroupMembership $membership): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || !$membership->businessGroup->canBusinessManageGroup($business->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $membership->update(['status' => 'active']);

            $membership->businessGroup->logActivity(
                'membership_activated',
                "Business '{$membership->business->name}' activated",
                $business->id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Membership activated successfully',
                'data' => $membership->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating membership', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate membership'
            ], 500);
        }
    }

    /**
     * Helper method to get current business
     */
    private function getCurrentBusiness($user): ?Business
    {
        $employee = $user->employee;
        return $employee ? $employee->business : null;
    }
}
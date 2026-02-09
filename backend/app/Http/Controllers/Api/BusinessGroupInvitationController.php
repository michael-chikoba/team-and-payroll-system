<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessGroupInvitation;
use App\Models\BusinessGroup;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;

use Illuminate\Support\Facades\Auth;


class BusinessGroupInvitationController extends Controller
{
    /**
     * Get all invitations (sent and received)
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

            // Get sent invitations (from groups where business is member)
            $sentInvitations = BusinessGroupInvitation::whereHas('businessGroup', function ($query) use ($business) {
                $query->whereHas('businesses', function ($q) use ($business) {
                    $q->where('businesses.id', $business->id);
                });
            })
            ->with(['businessGroup', 'invitedBusiness', 'invitedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

            // Get received invitations
            $receivedInvitations = $business->receivedGroupInvitations()
                ->with(['businessGroup', 'invitedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'sent' => $sentInvitations,
                    'received' => $receivedInvitations,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invitations', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invitations'
            ], 500);
        }
    }

    /**
     * Show single invitation
     */
    public function show(BusinessGroupInvitation $invitation, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            // Check if user has access to this invitation
            $hasAccess = $invitation->invited_business_id === $business->id ||
                         $business->isInGroup($invitation->business_group_id);

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $invitation->load(['businessGroup', 'invitedBusiness', 'invitedBy', 'respondedBy']);

            return response()->json([
                'success' => true,
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invitation', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invitation'
            ], 500);
        }
    }

 public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'business_group_id' => 'required|exists:business_groups,id',
        'email' => 'required|email',
        'proposed_role' => 'sometimes|required|in:owner,member,partner',
        'message' => 'nullable|string|max:1000',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = Auth::user();
    $employee = $user->employee;
    
    if (!$employee) {
        return response()->json([
            'success' => false,
            'message' => 'User is not associated with any business',
        ], 403);
    }
    
    $invitingBusiness = $employee->business;
    $groupId = $request->business_group_id;

    // Permission check
    if (!$invitingBusiness->canInviteToGroup($groupId)) {
        return response()->json([
            'success' => false,
            'message' => 'You are not allowed to invite businesses to this group',
        ], 403);
    }

    // FIXED: Find business by ANY admin email (not just primary)
    // Method 1: Through users who are admins
    $adminUser = User::where('email', $request->email)
        ->whereHas('employee', function ($q) {
            $q->whereNotNull('business_id');
        })
        ->first();

    if (!$adminUser) {
        return response()->json([
            'success' => false,
            'message' => 'No user found with this email address',
        ], 404);
    }

    // Check if user is an admin
    if (!$adminUser->hasRole('admin')) {
        return response()->json([
            'success' => false,
            'message' => 'This user is not an admin of any business',
        ], 422);
    }

    // Get the business through employee relationship
    $invitedBusiness = $adminUser->employee->business;

    if (!$invitedBusiness) {
        return response()->json([
            'success' => false,
            'message' => 'User is not associated with any business',
        ], 404);
    }

    // Prevent self-invite
    if ($invitedBusiness->id === $invitingBusiness->id) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot invite your own business',
        ], 422);
    }

    // Check if business is already in the group
    if ($invitedBusiness->isInGroup($groupId)) {
        return response()->json([
            'success' => false,
            'message' => 'This business is already a member of this group',
        ], 409);
    }

    // Check for existing pending invitation
    $existingInvite = BusinessGroupInvitation::where([
        'business_group_id' => $groupId,
        'invited_business_id' => $invitedBusiness->id,
        'status' => 'pending',
    ])->first();

    if ($existingInvite) {
        return response()->json([
            'success' => false,
            'message' => 'A pending invitation already exists for this business',
        ], 409);
    }

    try {
        DB::beginTransaction();

        // Create invitation
        $invitation = BusinessGroupInvitation::create([
            'business_group_id' => $groupId,
            'invited_business_id' => $invitedBusiness->id,
            'invited_by_user_id' => Auth::id(),
            'proposed_role' => $request->proposed_role ?? 'member',
            'message' => $request->message,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);


        $invitation->sendNotification();

        // Log activity
        $invitation->businessGroup->logActivity(
            'invitation_sent',
            "Invitation sent to business: {$invitedBusiness->name}",
            $invitingBusiness->id,
            $user->id
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Business invitation sent successfully',
            'data' => $invitation->load(['businessGroup', 'invitedBusiness', 'invitedBy'])
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Error sending invitation', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to send invitation: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Accept invitation
     */
    public function accept(Request $request, BusinessGroupInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || $invitation->invited_business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation is no longer valid'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $accepted = $invitation->accept($user, $request->message);

            if (!$accepted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to accept invitation'
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully',
                'data' => $invitation->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error accepting invitation', [
                'error' => $e->getMessage(),
                'invitation_id' => $invitation->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept invitation'
            ], 500);
        }
    }

    /**
     * Reject invitation
     */
    public function reject(Request $request, BusinessGroupInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business || $invitation->invited_business_id !== $business->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$invitation->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation is no longer valid'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $rejected = $invitation->reject($user, $request->message);

            if (!$rejected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject invitation'
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation rejected successfully',
                'data' => $invitation->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error rejecting invitation', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject invitation'
            ], 500);
        }
    }

    /**
     * Cancel invitation
     */
    public function cancel(BusinessGroupInvitation $invitation, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'No business association found'
            ], 404);
        }

        // Check if user can cancel (either invited by this user or business can manage group)
        $canCancel = $invitation->invited_by_user_id === $user->id ||
                     $invitation->businessGroup->canBusinessManageGroup($business->id);

        if (!$canCancel) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $cancelled = $invitation->cancel($user);

            if (!$cancelled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this invitation'
                ], 422);
            }

            $invitation->businessGroup->logActivity(
                'invitation_cancelled',
                "Invitation to business ID: {$invitation->invited_business_id} cancelled",
                $business->id,
                $user->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error cancelling invitation', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invitation'
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
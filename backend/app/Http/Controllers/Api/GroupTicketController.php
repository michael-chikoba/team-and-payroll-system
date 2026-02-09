<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\BusinessGroup;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class GroupTicketController extends Controller
{
    /**
     * Get all tickets across business group
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

            // Get business groups with ticket permissions
            $groupIds = $business->activeBusinessGroups()
                ->wherePivot('can_view_all_tickets', true)
                ->pluck('business_groups.id');

            if ($groupIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $query = Ticket::with(['user', 'approver', 'assignedUsers', 'businessGroup'])
                ->whereIn('business_group_id', $groupIds)
                ->where('is_group_ticket', true);

            // Apply filters
            if ($request->has('business_group_id')) {
                $query->where('business_group_id', $request->business_group_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('assigned_business_id')) {
                $query->where('assigned_business_id', $request->assigned_business_id);
            }

            $tickets = $query->latest()->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $tickets
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group tickets', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tickets'
            ], 500);
        }
    }

    /**
     * Create cross-business ticket
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_group_id' => 'required|exists:business_groups,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:issue,request,change_request',
            'category' => 'required|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'assigned_business_id' => 'nullable|exists:businesses,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
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

            $businessGroup = BusinessGroup::find($request->business_group_id);

            // Check if business is in group
            if (!$business->isInGroup($businessGroup->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your business is not part of this group'
                ], 403);
            }

            // Check if cross-business tickets are allowed
            if (!$businessGroup->allow_cross_business_tickets) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cross-business tickets are not enabled for this group'
                ], 403);
            }

            // Create ticket
            $ticket = Ticket::create([
                'business_group_id' => $businessGroup->id,
                'is_group_ticket' => true,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'subcategory' => $request->subcategory,
                'priority' => $request->priority,
                'user_id' => $user->id,
                'assigned_business_id' => $request->assigned_business_id,
                'due_date' => $request->due_date,
                'status' => 'pending',
            ]);

            // Assign users if provided
            if ($request->has('assigned_to') && !empty($request->assigned_to)) {
                foreach ($request->assigned_to as $userId) {
                    $ticket->assignedUsers()->attach($userId, [
                        'role' => 'assignee',
                        'assigned_at' => now()
                    ]);
                }
            }

            $businessGroup->logActivity(
                'group_ticket_created',
                "Group ticket '{$ticket->title}' created",
                $business->id,
                $user->id,
                ['ticket_id' => $ticket->id]
            );

            DB::commit();

            $ticket->load(['user', 'approver', 'assignedUsers', 'businessGroup']);

            return response()->json([
                'success' => true,
                'message' => 'Group ticket created successfully',
                'data' => $ticket
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating group ticket', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket'
            ], 500);
        }
    }

    /**
     * Show ticket details
     */
    public function show(Ticket $ticket, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canViewTicket($ticket, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $ticket->load([
            'user',
            'approver',
            'assignedUsers',
            'businessGroup',
            'comments.user',
            'attachments'
        ]);

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * Update ticket
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canEditTicket($ticket, $business, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:pending,approved,rejected,in_progress,resolved,closed',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ticket->update($request->only([
                'title',
                'description',
                'category',
                'subcategory',
                'priority',
                'status',
                'due_date'
            ]));

            if ($ticket->businessGroup) {
                $ticket->businessGroup->logActivity(
                    'group_ticket_updated',
                    "Group ticket '{$ticket->title}' updated",
                    $business->id,
                    $user->id,
                    ['ticket_id' => $ticket->id]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => $ticket->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating group ticket', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket'
            ], 500);
        }
    }

    /**
     * Assign ticket to a business
     */
    public function assignToBusiness(Request $request, Ticket $ticket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:businesses,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $business = $this->getCurrentBusiness($user);

            if (!$business || !$business->canAssignCrossBusinessTasks($ticket->business_group_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Verify target business is in the same group
            $targetBusiness = Business::find($request->business_id);
            if (!$targetBusiness->isInGroup($ticket->business_group_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target business is not in this group'
                ], 422);
            }

            DB::beginTransaction();

            $ticket->update([
                'assigned_business_id' => $request->business_id
            ]);

            // Assign specific users if provided
            if ($request->has('assigned_to')) {
                // Clear existing assignments
                $ticket->assignedUsers()->detach();
                
                // Add new assignments
                foreach ($request->assigned_to as $userId) {
                    $ticket->assignedUsers()->attach($userId, [
                        'role' => 'assignee',
                        'assigned_at' => now()
                    ]);
                }
            }

            $ticket->businessGroup->logActivity(
                'ticket_assigned_to_business',
                "Ticket assigned to business '{$targetBusiness->name}'",
                $business->id,
                $user->id,
                ['ticket_id' => $ticket->id, 'assigned_business_id' => $targetBusiness->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket assigned successfully',
                'data' => $ticket->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error assigning ticket to business', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign ticket'
            ], 500);
        }
    }

    /**
     * Get ticket comments
     */
    public function getComments(Ticket $ticket, Request $request): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canViewTicket($ticket, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $comments = $ticket->comments()
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Add comment to ticket
     */
    public function addComment(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        $business = $this->getCurrentBusiness($user);

        if (!$this->canViewTicket($ticket, $business)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $comment = $ticket->comments()->create([
                'user_id' => $user->id,
                'content' => $request->content,
                'is_internal' => $request->get('is_internal', false),
            ]);

            $ticket->businessGroup->logActivity(
                'comment_added',
                "Comment added to ticket '{$ticket->title}'",
                $business->id,
                $user->id,
                ['ticket_id' => $ticket->id, 'comment_id' => $comment->id]
            );

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment->load('user')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding comment', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment'
            ], 500);
        }
    }

    // Helper methods
    private function getCurrentBusiness($user): ?Business
    {
        $employee = $user->employee;
        return $employee ? $employee->business : null;
    }

    private function canViewTicket(Ticket $ticket, ?Business $business): bool
    {
        if (!$business || !$ticket->is_group_ticket) {
            return false;
        }

        return $business->canAccessGroupTickets($ticket->business_group_id);
    }

    private function canEditTicket(Ticket $ticket, ?Business $business, User $user): bool
    {
        if (!$this->canViewTicket($ticket, $business)) {
            return false;
        }

        // Ticket creator or assigned users can edit
        return $ticket->user_id === $user->id ||
               $ticket->assignedUsers()->where('user_id', $user->id)->exists();
    }
}
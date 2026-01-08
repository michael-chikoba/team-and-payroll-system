<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketApprovalRequest;
use App\Mail\TicketStatusUpdate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Get list of tickets with filtering
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user', 'approver']);

        // Role-based filtering
        if ($user->hasRole('approver')) {
            $query->where('approver_id', $user->id);
        } elseif (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Priority filter
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Business filter (for admin)
        if ($request->has('business_id') && $user->hasRole('admin')) {
            $query->whereHas('user.employee', function ($q) use ($request) {
                $q->where('business_id', $request->business_id);
            });
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * Create a new ticket
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'approver_id' => 'required|exists:users,id',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => Auth::id(),
                'approver_id' => $request->approver_id,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'status' => 'pending',
            ]);

            // Send email to approver
            $approver = User::find($request->approver_id);
            
            try {
                Mail::to($approver->email)->send(new TicketApprovalRequest($ticket));
            } catch (\Exception $e) {
                Log::warning('Failed to send ticket approval email', [
                    'ticket_id' => $ticket->id,
                    'approver_email' => $approver->email,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'Ticket created successfully',
                'ticket' => $ticket->load('approver')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create ticket', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show single ticket details
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();
        
        // Authorization check
        if (!$user->hasRole('admin') && 
            $ticket->user_id !== $user->id && 
            $ticket->approver_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($ticket->load(['user', 'approver']));
    }

    /**
     * Update ticket status (approve/reject/in-progress)
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Authorization check
        if ($ticket->approver_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,in_progress',
            'comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $ticket->status;
        
        $ticket->update([
            'status' => $request->status,
            'comments' => $request->comments,
            'approved_at' => $request->status === 'approved' ? now() : null,
        ]);

        // Send email to ticket creator about status update
        if ($oldStatus !== $request->status) {
            try {
                Mail::to($ticket->user->email)->send(new TicketStatusUpdate($ticket));
            } catch (\Exception $e) {
                Log::warning('Failed to send ticket status update email', [
                    'ticket_id' => $ticket->id,
                    'user_email' => $ticket->user->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'message' => 'Ticket status updated successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }

    /**
     * Update ticket (general update)
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Only ticket creator or admin can update
        if ($ticket->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:low,medium,high,critical',
            'due_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->update($request->only(['title', 'description', 'priority', 'due_date']));

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }

    /**
     * Delete ticket
     */
    public function destroy(Ticket $ticket)
    {
        $user = Auth::user();
        
        // Only ticket creator or admin can delete
        if ($ticket->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Get ticket count (for dashboard/stats)
     */
    public function count(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::query();

        // Apply role-based filtering
        if ($user->hasRole('approver')) {
            $query->where('approver_id', $user->id);
        } elseif (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'count' => $query->count()
        ]);
    }

    /**
     * Get ticket statistics (detailed breakdown)
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::query();

        // Apply role-based filtering
        if ($user->hasRole('approver')) {
            $query->where('approver_id', $user->id);
        } elseif (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Get counts by status
        $total = $query->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();

        // Get counts by priority
        $priorityCounts = [
            'low' => (clone $query)->where('priority', 'low')->count(),
            'medium' => (clone $query)->where('priority', 'medium')->count(),
            'high' => (clone $query)->where('priority', 'high')->count(),
            'critical' => (clone $query)->where('priority', 'critical')->count(),
        ];

        // Get overdue tickets
        $overdue = (clone $query)
            ->where('status', '!=', 'approved')
            ->where('due_date', '<', now())
            ->count();

        return response()->json([
            'total' => $total,
            'by_status' => [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'in_progress' => $inProgress,
            ],
            'by_priority' => $priorityCounts,
            'overdue' => $overdue,
        ]);
    }

    /**
     * Get list of approvers (Managers and Admins)
     */
    public function getApprovers(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found',
                'approvers' => []
            ], 404);
        }

        try {
            // Get managers and admins from the same business and country
            $approvers = User::select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->join('employees', 'users.id', '=', 'employees.user_id')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('employees.business_id', $employee->business_id)
                ->where('employees.country_id', $employee->country_id)
                ->where('employees.is_active', true)
                ->whereIn('roles.name', ['manager', 'admin'])
                ->where('model_has_roles.model_type', User::class)
                ->distinct()
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                    ];
                });

            return response()->json([
                'approvers' => $approvers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch approvers', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch approvers',
                'approvers' => []
            ], 500);
        }
    }

    /**
     * Get user's own tickets
     */
    public function myTickets(Request $request)
    {
        $user = Auth::user();
        
        $query = Ticket::where('user_id', $user->id)
            ->with(['approver']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * Get tickets assigned to user for approval
     */
    public function assignedToMe(Request $request)
    {
        $user = Auth::user();
        
        $query = Ticket::where('approver_id', $user->id)
            ->with(['user']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->latest()->paginate(10));
    }
}
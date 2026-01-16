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
        if ($user->hasRole('admin')) {
            // Admins see tickets from their business only
            $employee = $user->employee;
            if ($employee) {
                $query->whereHas('user.employee', function ($q) use ($employee) {
                    $q->where('business_id', $employee->business_id);
                });
            }
        } else {
            // Regular users only see their own tickets
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
        
        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
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
            DB::beginTransaction();
            
            $user = Auth::user();
            $employee = $user->employee;
            
            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact your administrator.'
                ], 403);
            }

            // Verify the selected approver is an admin from the same business
            $approver = User::with('employee')->find($request->approver_id);
           
            if (!$approver->hasRole('admin')) {
                return response()->json([
                    'message' => 'Selected approver must be an admin.'
                ], 422);
            }
            
            $approverEmployee = $approver->employee;
            if (!$approverEmployee || $approverEmployee->business_id !== $employee->business_id) {
                return response()->json([
                    'message' => 'Selected approver must be from your business.'
                ], 422);
            }

            $ticket = Ticket::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'approver_id' => $request->approver_id,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
                'status' => 'pending',
            ]);

            // Load relationships for email
            $ticket->load(['user', 'approver']);

            // Send email to approver - QUEUE IT for better reliability
            try {
                Log::info('Attempting to send approval email', [
                    'ticket_id' => $ticket->id,
                    'approver_email' => $approver->email,
                    'approver_name' => $approver->first_name . ' ' . $approver->last_name,
                    'config' => [
                        'mailer' => config('mail.default'),
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port'),
                    ]
                ]);

                // Test mail configuration first
                $this->testMailConfiguration();
                
                // Send email immediately
                Mail::to($approver->email)
                    ->send(new TicketApprovalRequest($ticket));

                Log::info('Ticket approval email sent successfully', [
                    'ticket_id' => $ticket->id,
                    'approver_email' => $approver->email
                ]);

                $emailSent = true;
                
            } catch (\Exception $e) {
                Log::error('Failed to send ticket approval email', [
                    'ticket_id' => $ticket->id,
                    'approver_email' => $approver->email,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);
                
                $emailSent = false;
                
                // Try alternative: Queue the email
                try {
                    Mail::to($approver->email)
                        ->queue(new TicketApprovalRequest($ticket));
                    
                    Log::info('Email queued for later delivery', [
                        'ticket_id' => $ticket->id,
                        'approver_email' => $approver->email
                    ]);
                    
                    $emailSent = true;
                } catch (\Exception $queueException) {
                    Log::error('Failed to queue email', [
                        'ticket_id' => $ticket->id,
                        'error' => $queueException->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Ticket created successfully',
                'ticket' => $ticket,
                'email_sent' => $emailSent ?? false
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create ticket', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           
            return response()->json([
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test mail configuration
     */
    private function testMailConfiguration()
    {
        try {
            // Get mail configuration
            $config = config('mail.mailers.smtp');
            
            if (!$config) {
                throw new \Exception('SMTP configuration not found');
            }
            
            Log::info('Mail Configuration', [
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'username' => $config['username'],
                'timeout' => $config['timeout'],
            ]);
            
            // Simple test - just log the configuration
            return true;
            
        } catch (\Exception $e) {
            Log::error('Mail configuration test failed', [
                'error' => $e->getMessage(),
                'config' => config('mail')
            ]);
            throw $e;
        }
    }

    /**
     * Update ticket status (approve/reject/in-progress)
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
       
        // Only admins can update status
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can update ticket status'], 403);
        }
        
        // Verify admin is from the same business
        $adminEmployee = $user->employee;
        $ticketUserEmployee = $ticket->user->employee;
       
        if (!$adminEmployee || !$ticketUserEmployee ||
            $adminEmployee->business_id !== $ticketUserEmployee->business_id) {
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
                Log::info('Sending status update email', [
                    'ticket_id' => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'user_email' => $ticket->user->email
                ]);

                Mail::to($ticket->user->email)->send(new TicketStatusUpdate($ticket));
                
                Log::info('Status update email sent successfully');
                
                $statusEmailSent = true;
                
            } catch (\Exception $e) {
                Log::error('Failed to send ticket status update email', [
                    'ticket_id' => $ticket->id,
                    'user_email' => $ticket->user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $statusEmailSent = false;
                
                // Try queuing instead
                try {
                    Mail::to($ticket->user->email)
                        ->queue(new TicketStatusUpdate($ticket));
                    
                    $statusEmailSent = true;
                    Log::info('Status update email queued');
                } catch (\Exception $queueEx) {
                    Log::error('Failed to queue status update email', [
                        'error' => $queueEx->getMessage()
                    ]);
                }
            }
        }
        
        // Format the response data
        $ticketData = $ticket->load(['user', 'approver'])->toArray();
       
        // Ensure approver has name field
        if (isset($ticketData['approver'])) {
            $ticketData['approver']['name'] = trim(
                ($ticketData['approver']['first_name'] ?? '') . ' ' .
                ($ticketData['approver']['last_name'] ?? '')
            );
        }
       
        // Ensure user has name field
        if (isset($ticketData['user'])) {
            $ticketData['user']['name'] = trim(
                ($ticketData['user']['first_name'] ?? '') . ' ' .
                ($ticketData['user']['last_name'] ?? '')
            );
        }
        
        return response()->json([
            'message' => 'Ticket status updated successfully',
            'ticket' => $ticketData,
            'email_sent' => $statusEmailSent ?? false
        ]);
    }

    /**
     * Show single ticket details
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();
       
        // Authorization check
        if ($user->hasRole('admin')) {
            // Admins can only see tickets from their business
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
           
            $ticketUserEmployee = $ticket->user->employee;
            if (!$ticketUserEmployee || $ticketUserEmployee->business_id !== $employee->business_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Format approver data consistently
        $ticketData = $ticket->load(['user', 'approver'])->toArray();
       
        // Ensure approver has name field
        if (isset($ticketData['approver'])) {
            $ticketData['approver']['name'] = trim(
                ($ticketData['approver']['first_name'] ?? '') . ' ' .
                ($ticketData['approver']['last_name'] ?? '')
            );
        }
       
        // Ensure user has name field
        if (isset($ticketData['user'])) {
            $ticketData['user']['name'] = trim(
                ($ticketData['user']['first_name'] ?? '') . ' ' .
                ($ticketData['user']['last_name'] ?? '')
            );
        }
        
        return response()->json($ticketData);
    }


    /**
     * Update ticket (general update)
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
       
        // Only ticket creator can update (before approval)
        if ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Prevent updates to approved/rejected tickets
        if (in_array($ticket->status, ['approved', 'rejected'])) {
            return response()->json([
                'message' => 'Cannot update ticket that has been approved or rejected'
            ], 422);
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
       
        // Format the response data
        $ticketData = $ticket->load(['user', 'approver'])->toArray();
       
        // Ensure approver has name field
        if (isset($ticketData['approver'])) {
            $ticketData['approver']['name'] = trim(
                ($ticketData['approver']['first_name'] ?? '') . ' ' .
                ($ticketData['approver']['last_name'] ?? '')
            );
        }
       
        // Ensure user has name field
        if (isset($ticketData['user'])) {
            $ticketData['user']['name'] = trim(
                ($ticketData['user']['first_name'] ?? '') . ' ' .
                ($ticketData['user']['last_name'] ?? '')
            );
        }
        
        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => $ticketData
        ]);
    }

    /**
     * Delete ticket
     */
    public function destroy(Ticket $ticket)
    {
        $user = Auth::user();
       
        // Only ticket creator can delete (and only if not approved)
        if ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if (in_array($ticket->status, ['approved', 'in_progress'])) {
            return response()->json([
                'message' => 'Cannot delete ticket that has been approved or is in progress'
            ], 422);
        }

        $ticket->delete();
        
        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Get ticket statistics (detailed breakdown)
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
       
        if ($user->hasRole('admin')) {
            // Admin sees stats for their business
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'in_progress' => 0,
                ]);
            }
            
            $query = Ticket::whereHas('user.employee', function ($q) use ($employee) {
                $q->where('business_id', $employee->business_id);
            });
        } else {
            // Regular user sees only their own tickets
            $query = Ticket::where('user_id', $user->id);
        }
        
        // Get counts by status
        $total = $query->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        
        return response()->json([
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'in_progress' => $inProgress,
        ]);
    }

    /**
     * Get list of approvers (Admins only from same business)
     * UPDATED: Simplified query to fetch admins from same business via employee table
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
            // Fetch business ID from current user's employee record
            $businessId = $employee->business_id;
            
            // Build query to get admins from the same business
            $approvers = User::whereHas('roles', function ($query) {
                // Filter users with 'admin' role
                $query->where('name', 'admin');
            })
            ->whereHas('employee', function ($query) use ($businessId) {
                // Filter employees in the same business
                $query->where('business_id', $businessId);
            })
            ->where('users.id', '!=', $user->id) // Exclude current user
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->with('employee') // Eager load employee relationship
            ->get();
            
            // Map the results to match frontend expectations
            $mappedApprovers = $approvers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'business_id' => $user->employee ? $user->employee->business_id : null,
                    'position' => $user->employee ? $user->employee->position : null
                ];
            });
            
            // Return approvers directly without nesting
            return response()->json($mappedApprovers);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch approvers', [
                'user_id' => $user->id,
                'business_id' => $employee->business_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch approvers',
                'error' => $e->getMessage(),
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
        
        $tickets = $query->latest()->paginate(10);
       
        // Format approver data in each ticket
        $tickets->getCollection()->transform(function ($ticket) {
            if ($ticket->approver) {
                $ticket->approver->name = trim(
                    ($ticket->approver->first_name ?? '') . ' ' .
                    ($ticket->approver->last_name ?? '')
                );
            }
            return $ticket;
        });
        
        return response()->json($tickets);
    }

    /**
     * Get tickets assigned to admin for approval (same business only)
     */
    public function assignedToMe(Request $request)
    {
        $user = Auth::user();
       
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only admins can access this endpoint',
                'data' => [],
                'meta' => []
            ], 403);
        }
        
        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'data' => [],
                'meta' => []
            ]);
        }
        
        $query = Ticket::with(['user'])
            ->where('approver_id', $user->id)
            ->whereHas('user.employee', function ($q) use ($employee) {
                $q->where('business_id', $employee->business_id);
            });
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        $tickets = $query->latest()->paginate(10);
       
        // Format user data in each ticket
        $tickets->getCollection()->transform(function ($ticket) {
            if ($ticket->user) {
                $ticket->user->name = trim(
                    ($ticket->user->first_name ?? '') . ' ' .
                    ($ticket->user->last_name ?? '')
                );
            }
            return $ticket;
        });
        
        return response()->json($tickets);
    }

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
     * Update ticket priority (Admin/Approver only)
     */
    public function updatePriority(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Only admins who are assigned approvers can update priority
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can update ticket priority'], 403);
        }
        
        // Verify admin is the assigned approver
        if ($ticket->approver_id !== $user->id) {
            return response()->json(['message' => 'You are not the assigned approver for this ticket'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:low,medium,high,critical',
            'update_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldPriority = $ticket->priority;
        
        $ticket->update([
            'priority' => $request->priority,
        ]);
        
        // Log the priority change
        Log::info('Ticket priority updated', [
            'ticket_id' => $ticket->id,
            'old_priority' => $oldPriority,
            'new_priority' => $request->priority,
            'updated_by' => $user->id,
            'reason' => $request->update_reason
        ]);

        return response()->json([
            'success' => true,  // Added success flag
            'message' => 'Ticket priority updated successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }

    /**
     * Reassign ticket to another approver (Admin/Approver only)
     */
    public function reassignTicket(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Only admins who are assigned approvers can reassign
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can reassign tickets'], 403);
        }
        
        // Verify admin is the assigned approver
        if ($ticket->approver_id !== $user->id) {
            return response()->json(['message' => 'You are not the assigned approver for this ticket'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'new_approver_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newApprover = User::with('employee')->find($request->new_approver_id);
        
        // Verify new approver is an admin from the same business
        if (!$newApprover->hasRole('admin')) {
            return response()->json([
                'message' => 'New approver must be an admin.'
            ], 422);
        }
        
        $userEmployee = $user->employee;
        $newApproverEmployee = $newApprover->employee;
        
        if (!$newApproverEmployee || $newApproverEmployee->business_id !== $userEmployee->business_id) {
            return response()->json([
                'message' => 'New approver must be from the same business.'
            ], 422);
        }

        $oldApproverId = $ticket->approver_id;
        
        $ticket->update([
            'approver_id' => $request->new_approver_id,
        ]);
        
        // Send email notification to new approver
        try {
            Mail::to($newApprover->email)->send(new \App\Mail\TicketApprovalRequest($ticket));
        } catch (\Exception $e) {
            Log::warning('Failed to send reassignment email', [
                'ticket_id' => $ticket->id,
                'new_approver_email' => $newApprover->email,
                'error' => $e->getMessage()
            ]);
        }
        
        // Log the reassignment
        Log::info('Ticket reassigned', [
            'ticket_id' => $ticket->id,
            'old_approver_id' => $oldApproverId,
            'new_approver_id' => $request->new_approver_id,
            'reassigned_by' => $user->id,
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,  // Added success flag
            'message' => 'Ticket reassigned successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }
}
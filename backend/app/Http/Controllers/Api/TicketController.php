<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use App\Models\TicketActivity;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketApprovalRequest;
use App\Mail\TicketStatusUpdate;
use App\Mail\TicketAssigned;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\TicketNotificationService;


class TicketController extends Controller
{

protected $notificationService;

public function __construct(TicketNotificationService $notificationService)
{
    $this->notificationService = $notificationService;
}
    /**
     * Get list of tickets with filtering (BACKWARD COMPATIBLE)
     */
   /**
 * Get list of tickets with filtering (BACKWARD COMPATIBLE)
 */
public function index(Request $request)
{
    $user = $request->user();
    
    // Add detailed request logging
    Log::info('TICKETS INDEX: Request received', [
        'user_id' => $user->id,
        'user_name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
        'has_role_admin' => $user->hasRole('admin') ? 'yes' : 'no',
        'employee_exists' => $user->employee ? 'yes' : 'no',
        'business_id' => $user->employee ? $user->employee->business_id : 'none',
        'request_params' => $request->all(),
        'url' => $request->fullUrl(),
        'ip' => $request->ip()
    ]);
    
    // Use the new index method if we have new features enabled
    if ($request->has('type') || $request->has('category') || $request->has('department_id')) {
        Log::info('TICKETS INDEX: Using enhanced index method');
        return $this->indexEnhanced($request);
    }
    
    $query = Ticket::with(['user', 'approver', 'assignedUsers']);
    
    // Role-based filtering - APPLIED FIRST
    if ($user->hasRole('admin')) {
        // Admins see tickets from their business only
        $employee = $user->employee;
        if ($employee) {
            $query->whereHas('user.employee', function ($q) use ($employee) {
                $q->where('business_id', $employee->business_id);
            });
            
            Log::info('TICKETS INDEX: Admin filtering applied', [
                'business_id' => $employee->business_id,
                'employee_id' => $employee->id,
                'ticket_count_before_filter' => Ticket::count()
            ]);
        } else {
            Log::warning('TICKETS INDEX: Admin user has no employee profile', [
                'user_id' => $user->id
            ]);
            return response()->json(['data' => [], 'message' => 'No employee profile found'], 200);
        }
    } else {
        // Regular users only see their own tickets
        $query->where('user_id', $user->id);
        
        Log::info('TICKETS INDEX: Regular user filtering applied', [
            'user_id' => $user->id,
            'ticket_count_before_filter' => Ticket::count()
        ]);
    }
    
    // 🔍 DEBUG LOG - MOVED HERE: Show filtered results BEFORE additional filters
    Log::info('TICKETS INDEX: Tickets after role filtering', [
        'count' => $query->count(),
        'first_ticket_id' => $query->first()?->id,
        'first_ticket_user_id' => $query->first()?->user_id,
        'first_ticket_assigned_users' => $query->first()?->assignedUsers?->pluck('id')->toArray(),
        'user_role' => $user->hasRole('admin') ? 'admin' : 'regular'
    ]);
    
    // Status filter
    if ($request->has('status')) {
        $query->where('status', $request->status);
        
        Log::info('TICKETS INDEX: Status filter applied', [
            'status' => $request->status
        ]);
    }
    
    // Priority filter
    if ($request->has('priority')) {
        $query->where('priority', $request->priority);
        
        Log::info('TICKETS INDEX: Priority filter applied', [
            'priority' => $request->priority
        ]);
    }
    
    // Search filter
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('id', 'like', "%{$search}%");
        });
        
        Log::info('TICKETS INDEX: Search filter applied', [
            'search_term' => $search
        ]);
    }
    
    // Get final results before pagination
    $finalCount = $query->count();
    $firstTicket = $query->first();
    
    // 🔍 FINAL DEBUG LOG
    Log::info('TICKETS INDEX: Returning filtered tickets', [
        'final_count' => $finalCount,
        'first_ticket_id' => $firstTicket?->id,
        'first_ticket_title' => $firstTicket?->title,
        'first_ticket_status' => $firstTicket?->status,
        'first_ticket_user_id' => $firstTicket?->user_id,
        'first_ticket_assigned_users' => $firstTicket?->assignedUsers?->pluck('id')->toArray(),
        'user_id' => $user->id,
        'is_admin' => $user->hasRole('admin') ? 'yes' : 'no',
        'applied_filters' => [
            'status' => $request->has('status') ? $request->status : 'none',
            'priority' => $request->has('priority') ? $request->priority : 'none',
            'search' => $request->has('search') && !empty($request->search) ? 'yes' : 'no'
        ]
    ]);
    
    // Apply pagination
    $perPage = $request->get('per_page', 10);
    $page = $request->get('page', 1);
    
    $paginatedTickets = $query->latest()->paginate($perPage, ['*'], 'page', $page);
    
    return response()->json($paginatedTickets);
}

    /**
     * Enhanced index method with new features
     */
    public function indexEnhanced(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user', 'approver', 'assignedUsers']);
        
        // Role-based filtering
        if ($user->hasRole('admin')) {
            $employee = $user->employee;
            if ($employee) {
                $query->whereHas('user.employee', function ($q) use ($employee) {
                    $q->where('business_id', $employee->business_id);
                });
            }
        } else {
            // Regular users see their tickets and tickets assigned to them
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('assignedUsers', function ($assignQ) use ($user) {
                      $assignQ->where('user_id', $user->id);
                  });
            });
        }
        
        // Type filter
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Category filter
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Assigned to filter
        if ($request->has('assigned_to')) {
            $query->whereHas('assignedUsers', function ($q) use ($request) {
                $q->where('user_id', $request->assigned_to);
            });
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
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('subcategory', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * Get ticket types with their configurations
     */
    public function getTicketTypes(Request $request)
    {
        try {
            // First check if TicketType model exists
            if (!class_exists(TicketType::class)) {
                // Return default types if table doesn't exist yet
                return response()->json([
                    [
                        'slug' => 'issue',
                        'name' => 'Issue',
                        'description' => 'Report problems, bugs, or system errors',
                        'icon' => 'exclamation-circle',
                        'color' => '#EF4444',
                        'categories' => ['Technical', 'System', 'Access', 'Performance', 'Other'],
                        'subcategories' => ['Login', 'Slow Performance', 'Error Message', 'Data Issue', 'Bug'],
                        'sla_hours' => 24,
                        'requires_approval' => false,
                        'is_active' => true,
                    ],
                    [
                        'slug' => 'request',
                        'name' => 'Request',
                        'description' => 'Request for new features, access, or information',
                        'icon' => 'document-text',
                        'color' => '#10B981',
                        'categories' => ['Access', 'Hardware', 'Software', 'Data', 'Training'],
                        'subcategories' => ['New Account', 'Permission', 'Report', 'Equipment', 'Consultation'],
                        'sla_hours' => 48,
                        'requires_approval' => true,
                        'is_active' => true,
                    ],
                    [
                        'slug' => 'change_request',
                        'name' => 'Change Request',
                        'description' => 'Request for system or process changes',
                        'icon' => 'adjustments',
                        'color' => '#8B5CF6',
                        'categories' => ['Process', 'System', 'Policy', 'Configuration', 'Integration'],
                        'subcategories' => ['Enhancement', 'Modification', 'Deployment', 'Integration', 'Customization'],
                        'sla_hours' => 72,
                        'requires_approval' => true,
                        'is_active' => true,
                    ]
                ]);
            }
            
            $types = TicketType::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
                
            return response()->json($types);
        } catch (\Exception $e) {
            Log::error('Failed to fetch ticket types', ['error' => $e->getMessage()]);
            return response()->json([]);
        }
    }

    /**
     * Get categories for a specific ticket type
     */
    public function getCategories(Request $request, $typeSlug)
    {
        try {
            if (!class_exists(TicketType::class)) {
                // Return default categories based on type
                $defaultCategories = [
                    'issue' => [
                        'categories' => ['Technical', 'System', 'Access', 'Performance', 'Other'],
                        'subcategories' => ['Login', 'Slow Performance', 'Error Message', 'Data Issue', 'Bug']
                    ],
                    'request' => [
                        'categories' => ['Access', 'Hardware', 'Software', 'Data', 'Training'],
                        'subcategories' => ['New Account', 'Permission', 'Report', 'Equipment', 'Consultation']
                    ],
                    'change_request' => [
                        'categories' => ['Process', 'System', 'Policy', 'Configuration', 'Integration'],
                        'subcategories' => ['Enhancement', 'Modification', 'Deployment', 'Integration', 'Customization']
                    ]
                ];
                
                return response()->json($defaultCategories[$typeSlug] ?? [
                    'categories' => [],
                    'subcategories' => []
                ]);
            }
            
            $ticketType = TicketType::where('slug', $typeSlug)->first();
            
            if (!$ticketType) {
                return response()->json([
                    'categories' => [],
                    'subcategories' => []
                ]);
            }
            
            return response()->json([
                'categories' => $ticketType->categories ?? [],
                'subcategories' => $ticketType->subcategories ?? []
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories', ['type' => $typeSlug, 'error' => $e->getMessage()]);
            return response()->json([
                'categories' => [],
                'subcategories' => []
            ]);
        }
    }

    /**
     * Create a new ticket (BACKWARD COMPATIBLE + NEW FEATURES)
     */
    public function store(Request $request)
    {
        // Determine if this is a new-style ticket with type
        if ($request->has('type')) {
            return $this->storeWithType($request);
        }
        
        // Old-style ticket creation (backward compatible)
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
                'type' => 'request',
                'category' => 'General',
            ]);

            // Load relationships
            $ticket->load(['user', 'approver']);

            // Get all admins from the same business to notify
            $businessAdmins = $this->getBusinessAdmins($employee->business_id);
            
            // Send email to all admins
            $emailsSent = 0;
            foreach ($businessAdmins as $admin) {
                try {
                    Mail::to($admin->email)->send(new TicketApprovalRequest($ticket));
                    $emailsSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send approval email to admin', [
                        'ticket_id' => $ticket->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Ticket created successfully',
                'ticket' => $ticket,
                'emails_sent' => $emailsSent,
                'admins_notified' => count($businessAdmins)
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create ticket', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
           
            return response()->json([
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
 * Create a new ticket with type support - ENHANCED with better data handling
 */
public function storeWithType(Request $request)
{
    Log::info('Ticket creation request received', [
        'user_id' => Auth::id(),
        'data' => $request->all(),
        'has_assigned_to' => $request->has('assigned_to'),
        'assigned_to_type' => gettype($request->input('assigned_to')),
        'assigned_to_value' => $request->input('assigned_to')
    ]);

    // Handle different formats of assigned_to
    $assignedToInput = $request->input('assigned_to');
    $assignedToArray = [];
    
    if ($assignedToInput) {
        if (is_array($assignedToInput)) {
            $assignedToArray = $assignedToInput;
        } elseif (is_string($assignedToInput)) {
            // Handle comma-separated string or JSON string
            if (Str::contains($assignedToInput, ',')) {
                $assignedToArray = explode(',', $assignedToInput);
            } elseif (Str::startsWith($assignedToInput, '[') && Str::endsWith($assignedToInput, ']')) {
                $assignedToArray = json_decode($assignedToInput, true);
            } else {
                $assignedToArray = [$assignedToInput];
            }
        } elseif (is_numeric($assignedToInput)) {
            $assignedToArray = [$assignedToInput];
        }
        
        // Clean and validate the array
        $assignedToArray = array_filter($assignedToArray, function($item) {
            return !empty($item) && is_numeric($item);
        });
        $assignedToArray = array_values(array_unique($assignedToArray));
    }

    Log::info('Processed assigned_to array', [
        'original' => $assignedToInput,
        'processed' => $assignedToArray,
        'count' => count($assignedToArray)
    ]);

    $validator = Validator::make($request->all(), [
        'type' => 'required|in:issue,request,change_request',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'category' => 'required|string|max:100',
        'subcategory' => 'nullable|string|max:100',
        'approver_id' => 'nullable|exists:users,id',
        'priority' => 'required|in:low,medium,high,critical',
        'due_date' => 'nullable|date|after_or_equal:today',
        'estimated_hours' => 'nullable|numeric|min:0|max:1000',
        'assigned_to' => 'nullable', // Changed from array validation
        'assigned_to.*' => 'exists:users,id', // Still validate each ID
    ]);

    // Manual validation for approver_id when required
    if (in_array($request->type, ['request', 'change_request']) && !$request->approver_id) {
        $validator->after(function ($validator) {
            $validator->errors()->add('approver_id', 'Approver is required for this ticket type.');
        });
    }

    if ($validator->fails()) {
        Log::error('Ticket validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request' => $request->all()
        ]);
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();
        
        $user = Auth::user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found.'
            ], 403);
        }

        // Determine initial status based on type
        $requiresApproval = in_array($request->type, ['request', 'change_request']);
        $initialStatus = $requiresApproval ? 'pending' : 'in_progress';

        // Validate approver for request types
        if ($requiresApproval && $request->approver_id) {
            $approver = User::with('employee')->find($request->approver_id);
            
            if (!$approver || !$approver->hasRole('admin')) {
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
        }

        $ticketData = [
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $user->id,
            'category' => $request->category,
            'subcategory' => $request->subcategory,
            'approver_id' => $requiresApproval ? $request->approver_id : null,
            'priority' => $request->priority,
            'due_date' => $request->due_date ?: null,
            'estimated_hours' => $request->estimated_hours,
            'status' => $initialStatus,
        ];

        $ticket = Ticket::create($ticketData);
        
        // 🔥 ENHANCED ASSIGNMENT LOGIC with better data handling
        $assignmentLog = [];
        $assignedUsers = [];
        
        // If specific users are assigned
        if (!empty($assignedToArray)) {
            Log::info('Processing manual assignments', [
                'ticket_id' => $ticket->id,
                'assigned_to_array' => $assignedToArray,
                'count' => count($assignedToArray)
            ]);
            
            foreach ($assignedToArray as $assignedUserId) {
                if ($assignedUserId) {
                    $this->assignUserToTicket($ticket, $assignedUserId, 'assignee');
                    $assignedUsers[] = $assignedUserId;
                    $assignmentLog[] = "Assigned to user $assignedUserId as assignee";
                }
            }
        } 
        // AUTO-ASSIGNMENT LOGIC if no assignment provided
        else {
            Log::info('No manual assignments, using auto-assignment logic');
            
            // For issues: auto-assign to creator if no one assigned
            if ($request->type === 'issue') {
                $this->assignUserToTicket($ticket, $user->id, 'assignee');
                $assignedUsers[] = $user->id;
                $assignmentLog[] = "Auto-assigned to creator (user $user->id) as assignee";
            }
            // For requests/change requests: auto-assign to approver as reviewer
            elseif ($requiresApproval && $request->approver_id) {
                $this->assignUserToTicket($ticket, $request->approver_id, 'reviewer');
                $assignedUsers[] = $request->approver_id;
                $assignmentLog[] = "Auto-assigned to approver (user $request->approver_id) as reviewer";
            }
            // Fallback: assign to creator
            else {
                $this->assignUserToTicket($ticket, $user->id, 'assignee');
                $assignedUsers[] = $user->id;
                $assignmentLog[] = "Fallback assigned to creator (user $user->id) as assignee";
            }
        }

        // Load relationships
        $ticket->load(['user', 'approver', 'assignedUsers']);
        
        // Send notification
        $this->notificationService->notifyTicketCreated($ticket);

        // Send notifications to ALL business admins if approval is required
        $emailsSent = 0;
        $adminsNotified = 0;
        
        if ($requiresApproval) {
            $businessAdmins = $this->getBusinessAdmins($employee->business_id);
            $adminsNotified = count($businessAdmins);
            
            foreach ($businessAdmins as $admin) {
                try {
                    Mail::to($admin->email)->send(new TicketApprovalRequest($ticket));
                    $emailsSent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send approval email to admin', [
                        'ticket_id' => $ticket->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        DB::commit();
        
        Log::info('Ticket created successfully', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'emails_sent' => $emailsSent,
            'admins_notified' => $adminsNotified,
            'assignments' => $assignmentLog,
            'assigned_users' => $assignedUsers,
            'assigned_users_count' => count($assignedUsers)
        ]);
        
        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => $ticket,
            'emails_sent' => $emailsSent,
            'admins_notified' => $adminsNotified,
            'assignments' => $assignmentLog,
            'assigned_users' => $assignedUsers,
            'assigned_users_count' => count($assignedUsers)
        ], 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Failed to create ticket', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'message' => 'Failed to create ticket',
            'error' => $e->getMessage()
        ], 500);
    }
}
/**
 * Helper method to assign user to ticket with notifications
 */
private function assignUserToTicket(Ticket $ticket, $userId, $role = 'assignee')
{
    try {
        // Check if already assigned to avoid duplicates
        $alreadyAssigned = $ticket->assignedUsers()
            ->where('user_id', $userId)
            ->exists();
        
        if (!$alreadyAssigned) {
            $ticket->assignedUsers()->attach($userId, [
                'role' => $role,
                'assigned_at' => now()
            ]);
            
            Log::info('User assigned to ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'role' => $role,
                'assigned_at' => now()->toDateTimeString()
            ]);
            
            // Send assignment notification
            $assignedUser = User::find($userId);
            if ($assignedUser) {
                try {
                    Mail::to($assignedUser->email)->send(new TicketAssigned($ticket, $assignedUser, $role));
                    Log::info('Assignment email sent', [
                        'ticket_id' => $ticket->id,
                        'assigned_user_id' => $userId,
                        'email' => $assignedUser->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send assignment email', [
                        'ticket_id' => $ticket->id,
                        'assigned_user' => $userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return true;
        }
        
        Log::warning('User already assigned to ticket', [
            'ticket_id' => $ticket->id,
            'user_id' => $userId
        ]);
        
        return false;
        
    } catch (\Exception $e) {
        Log::error('Failed to assign user to ticket', [
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        
        throw $e;
    }
}
   
    /**
     * Update ticket status with workflow validation
     * UPDATED: Now allows any admin from the same business to approve
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Check permissions - UPDATED LOGIC
        $canUpdate = false;
        
        if ($user->hasRole('admin')) {
            // Any admin from the same business can update tickets
            $adminEmployee = $user->employee;
            $ticketUserEmployee = $ticket->user->employee;
            
            if ($adminEmployee && $ticketUserEmployee && 
                $adminEmployee->business_id === $ticketUserEmployee->business_id) {
                $canUpdate = true;
            }
        } elseif ($ticket->assignedUsers()->where('user_id', $user->id)->exists()) {
            // Assigned users can update tickets assigned to them
            $canUpdate = true;
        }
        
        if (!$canUpdate) {
            return response()->json(['message' => 'Unauthorized to update this ticket'], 403);
        }

        // Get available statuses based on current status
        $availableStatuses = $this->getAvailableStatuses($ticket->status);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', $availableStatuses),
            'comments' => 'nullable|string|max:1000',
            'send_email' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $ticket->status;
        
        $updates = [
            'status' => $request->status,
        ];

        if ($request->filled('comments')) {
            $updates['comments'] = $request->comments;
        }

        // Handle status-specific updates
        if ($request->status === 'resolved') {
            $updates['resolved_at'] = now();
        } elseif ($request->status === 'closed') {
            $updates['closed_at'] = now();
        } elseif ($request->status === 'approved') {
            $updates['approved_at'] = now();
            $updates['approved_by'] = $user->id;
            // Update the approver_id to the admin who actually approved it
            $updates['approver_id'] = $user->id;
        } elseif ($request->status === 'rejected') {
            $updates['rejected_at'] = now();
            $updates['rejected_by'] = $user->id;
        } elseif ($request->status === 'in_progress') {
            $updates['started_at'] = now();
        }

        Log::info('Updating ticket status', [
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updates' => $updates,
            'user_id' => $user->id,
            'is_original_approver' => $ticket->approver_id === $user->id,
        ]);

        try {
            $ticket->update($updates);
            $ticket->refresh();
            
            // Log activity
            $actionDescription = "Status changed from {$oldStatus} to {$request->status}";
            if ($request->status === 'approved' && $ticket->approver_id !== $user->id) {
                $actionDescription .= " (approved by alternate admin)";
            }
            $this->logActivity($ticket, 'status_updated', $actionDescription, $user);
// ✨ NEW: Send notifications for important status changes
        if ($request->status === 'approved') {
            $this->notificationService->notifyTicketApproved($ticket);
        } elseif (in_array($request->status, ['resolved', 'closed', 'in_progress'])) {
            $this->notificationService->notifyStatusChanged($ticket, $oldStatus, $request->status);
        }
            // Send notification if requested
            if ($request->filled('send_email') && $request->send_email && $ticket->user_id !== $user->id) {
                try {
                    Mail::to($ticket->user->email)
                        ->send(new TicketStatusUpdate($ticket, $oldStatus, $request->status));
                } catch (\Exception $e) {
                    Log::error('Failed to send status update email', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket status updated successfully',
                'ticket' => $ticket->load(['user', 'approver', 'assignedUsers']),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update ticket status', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'query' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available statuses based on current status
     */
    private function getAvailableStatuses($currentStatus)
    {
        $statusTransitions = [
            'pending' => ['approved', 'rejected', 'in_progress'],
            'in_progress' => ['resolved', 'closed', 'reopened'],
            'resolved' => ['closed', 'reopened'],
            'closed' => ['reopened'],
            'reopened' => ['in_progress', 'resolved', 'closed'],
            'approved' => ['in_progress', 'rejected'],
            'rejected' => ['reopened'],
        ];
        
        return $statusTransitions[$currentStatus] ?? [];
    }

    /**
     * Assign ticket to users
     * UPDATED: Any admin from the same business can assign tickets
     */
   /**
 * Assign ticket to users - ENHANCED with better validation
 */
public function assignTicket(Request $request, Ticket $ticket)
{
    $user = Auth::user();
    
    // Check if user can assign tickets - UPDATED LOGIC
    $canAssign = false;
    
    if ($user->hasRole('admin')) {
        $adminEmployee = $user->employee;
        $ticketUserEmployee = $ticket->user->employee;
        
        if ($adminEmployee && $ticketUserEmployee && 
            $adminEmployee->business_id === $ticketUserEmployee->business_id) {
            $canAssign = true;
        }
    }
    // Allow ticket creator to assign users too
    elseif ($ticket->user_id === $user->id) {
        $canAssign = true;
    }
    
    if (!$canAssign) {
        return response()->json(['message' => 'Unauthorized to assign this ticket'], 403);
    }

    $validator = Validator::make($request->all(), [
        'assigned_to' => 'required|array|min:1',
        'assigned_to.*' => 'exists:users,id',
        'role' => 'required|in:assignee,reviewer,implementer',
        'clear_existing' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();
        
        $assignmentLog = [];
        $newAssignments = [];
        
        // Clear existing assignments if requested
        if ($request->filled('clear_existing') && $request->clear_existing) {
            $ticket->assignedUsers()->detach();
            $assignmentLog[] = 'Cleared existing assignments';
        }
        
        // Assign new users
        foreach ($request->assigned_to as $assignedUserId) {
            $assigned = $this->assignUserToTicket($ticket, $assignedUserId, $request->role);
            if ($assigned) {
                $newAssignments[] = $assignedUserId;
                $assignmentLog[] = "Assigned user $assignedUserId as {$request->role}";
            }
        }

        // Log activity
        $this->logActivity($ticket, 'assigned', 
            "Ticket assigned to " . count($newAssignments) . " user(s): " . implode(', ', $newAssignments), 
            $user
        );

        DB::commit();

        Log::info('Ticket assignment completed', [
            'ticket_id' => $ticket->id,
            'assigned_by' => $user->id,
            'new_assignments' => $newAssignments,
            'role' => $request->role,
            'assignment_log' => $assignmentLog
        ]);

        return response()->json([
            'message' => 'Ticket assigned successfully',
            'ticket' => $ticket->load('assignedUsers'),
            'assignments' => $assignmentLog,
            'new_assignments' => $newAssignments
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Failed to assign ticket', [
            'ticket_id' => $ticket->id,
            'assigned_by' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to assign ticket: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get tickets assigned to current user
 */
public function assignedTickets(Request $request)
{
    $user = Auth::user();
    
    Log::info('ASSIGNED TICKETS: Request received', [
        'user_id' => $user->id,
        'user_name' => $user->name ?? ($user->first_name . ' ' . $user->last_name),
        'request_params' => $request->all()
    ]);
    
    $query = Ticket::with(['user', 'approver', 'assignedUsers'])
        ->whereHas('assignedUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    
    // Status filter
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }
    
    // Priority filter
    if ($request->has('priority')) {
        $query->where('priority', $request->priority);
    }
    
    // Type filter
    if ($request->has('type')) {
        $query->where('type', $request->type);
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
    
    $assignedCount = $query->count();
    
    Log::info('ASSIGNED TICKETS: Found tickets', [
        'user_id' => $user->id,
        'assigned_ticket_count' => $assignedCount
    ]);
    
    $tickets = $query->latest()->paginate($request->get('per_page', 10));
    
    return response()->json([
        'message' => 'Assigned tickets retrieved successfully',
        'total_assigned' => $assignedCount,
        'data' => $tickets->items(),
        'meta' => [
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
            'per_page' => $tickets->perPage(),
            'total' => $tickets->total(),
            'from' => $tickets->firstItem(),
            'to' => $tickets->lastItem(),
        ]
    ]);
}

    /**
 * Get ticket statistics
 * UPDATED: Fixed total count calculation and added SLA compliance
 */
public function statistics(Request $request)
{
    $user = Auth::user();
    $employee = $user->employee;
    
    // Build base query based on user role
    if ($user->hasRole('admin') && $employee) {
        $query = Ticket::whereHas('user.employee', function ($q) use ($employee) {
            $q->where('business_id', $employee->business_id);
        });
    } else {
        $query = Ticket::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('assignedUsers', function ($assignQ) use ($user) {
                  $assignQ->where('user_id', $user->id);
              });
        });
    }

    // Clone the query for different aggregations
    $baseQuery = clone $query;

    // Get total count - FIX: This should count all tickets, not just one
    $total = (clone $query)->count();

    // Get counts by status
    $byStatus = (clone $query)->selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

    // Get counts by type with status breakdown
    $byTypeRaw = (clone $query)->selectRaw('type, status, count(*) as count')
        ->groupBy('type', 'status')
        ->get();

    $byType = $byTypeRaw->groupBy('type')->map(function ($items) {
        return $items->map(function ($item) {
            return [
                'type' => $item->type,
                'count' => $item->count,
                'status' => $item->status,
                'status_label' => ucfirst(str_replace('_', ' ', $item->status)),
            ];
        })->values();
    });

    // Get counts by priority
    $byPriority = (clone $query)->selectRaw('priority, count(*) as count')
        ->groupBy('priority')
        ->pluck('count', 'priority')
        ->toArray();

    // Get status counts
    $pending = (clone $query)->where('status', 'pending')->count();
    $approved = (clone $query)->where('status', 'approved')->count();
    $rejected = (clone $query)->where('status', 'rejected')->count();
    $inProgress = (clone $query)->where('status', 'in_progress')->count();
    $resolved = (clone $query)->where('status', 'resolved')->count();
    $closed = (clone $query)->where('status', 'closed')->count();

    // Get overdue tickets (past due date and not resolved/closed)
    $overdue = (clone $query)
        ->where('due_date', '<', now())
        ->whereNotIn('status', ['resolved', 'closed'])
        ->count();

    // Calculate SLA Compliance - NEW ADDITION
    $slaTrackedTickets = (clone $query)
        ->whereNotNull('due_date')
        ->count();

    if ($slaTrackedTickets > 0) {
        // Tickets that meet SLA:
        // 1. Resolved before or on due date
        // 2. Not yet resolved but still within due date
        $slaCompliantTickets = (clone $query)
            ->whereNotNull('due_date')
            ->where(function ($q) {
                // Resolved tickets that met deadline
                $q->where(function ($resolved) {
                    $resolved->whereNotNull('resolved_at')
                           ->whereColumn('resolved_at', '<=', 'due_date');
                })
                // Active tickets still within deadline
                ->orWhere(function ($active) {
                    $active->whereNull('resolved_at')
                         ->where('due_date', '>=', now());
                });
            })
            ->count();

        $slaPercentage = round(($slaCompliantTickets / $slaTrackedTickets) * 100);
    } else {
        $slaCompliantTickets = 0;
        $slaPercentage = 0;
    }

    return response()->json([
        // Main statistics
        'total' => $total,
        'pending' => $pending,
        'approved' => $approved,
        'rejected' => $rejected,
        'in_progress' => $inProgress,
        'resolved' => $resolved,
        'closed' => $closed,
        'overdue' => $overdue,
        
        // Breakdowns
        'by_type' => $byType,
        'by_status' => $byStatus,
        'by_priority' => $byPriority,
        
        // SLA Compliance - NEW
        'sla_compliance' => [
            'compliant' => $slaCompliantTickets,
            'total' => $slaTrackedTickets,
            'percentage' => $slaPercentage,
        ],
    ]);
}

    /**
     * Get departments for ticket assignment - DISABLED
     */
    public function getDepartments(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([]);
            }

            return response()->json([]);
            
        } catch (\Exception $e) {
            Log::error('TICKET_CONTROLLER: Failed to fetch departments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? null,
            ]);
            
            return response()->json([], 500);
        }
    }

    /**
     * Helper method to get all admins from a business
     */
    private function getBusinessAdmins($businessId)
    {
        return User::whereHas('employee', function ($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
        ->where(function ($q) {
            $q->where('role', 'admin')
              ->orWhereHas('roles', function ($roleQuery) {
                  $roleQuery->where('name', 'admin');
              });
        })
        ->get();
    }

    /**
     * Get users available for assignment AND approvers - COMBINED METHOD
     * UPDATED: Added detailed logging for users and approvers with their roles
     */
    public function getAssignableUsers(Request $request)
    {
        try {
            $startTime = microtime(true);
            $user = Auth::user();
            $employee = $user->employee;

            // Log::info('TICKET_CONTROLLER: Getting assignable users and approvers', [
            //     'user_id' => $user->id,
            //     'user_role' => $user->role,
            //     'has_employee' => $employee ? 'yes' : 'no',
            // ]);

            if (!$employee) {
                Log::warning('TICKET_CONTROLLER: No employee profile found for user', [
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'assignable_users' => [],
                    'approvers' => []
                ]);
            }

            $employees = Employee::where('business_id', $employee->business_id)
                ->where('id', '!=', $employee->id)
                ->with(['user'])
                ->get();

            // Log::info('TICKET_CONTROLLER: Retrieved employees', [
            //     'employee_count' => $employees->count(),
            //     'business_id' => $employee->business_id,
            // ]);

            $assignableUsers = $employees->map(function ($employeeItem) {
                $userRole = $employeeItem->user->role ?? 'employee';
                
                // Check BOTH role column AND hasRole method for admin status
                // This handles both role systems (column-based and relationship-based)
                $hasRoleMethod = $employeeItem->user->hasRole('admin') ?? false;
                $hasRoleColumn = ($userRole === 'admin');
                $isAdmin = $hasRoleColumn || $hasRoleMethod;
                
                $userData = [
                    'id' => $employeeItem->user_id,
                    'employee_id' => $employeeItem->id,
                    'employee_number' => $employeeItem->employee_id,
                    'first_name' => $employeeItem->user->first_name ?? $employeeItem->first_name,
                    'last_name' => $employeeItem->user->last_name ?? $employeeItem->last_name,
                    'name' => trim(($employeeItem->user->first_name ?? $employeeItem->first_name) . ' ' . ($employeeItem->user->last_name ?? $employeeItem->last_name)),
                    'email' => $employeeItem->user->email ?? $employeeItem->email,
                    'role' => $userRole,
                    'position' => $employeeItem->position,
                    'department' => $employeeItem->department,
                    'employment_type' => $employeeItem->employment_type,
                    'hire_date' => $employeeItem->hire_date,
                    'is_active' => true,
                    'is_admin' => $isAdmin,
                ];
                
                // // Log each user with their role details
                // Log::info('TICKET_CONTROLLER: Mapped assignable user', [
                //     'user_id' => $userData['id'],
                //     'name' => $userData['name'],
                //     'email' => $userData['email'],
                //     'role' => $userRole,
                //     'role_column_is_admin' => $hasRoleColumn,
                //     'hasRole_method_result' => $hasRoleMethod,
                //     'final_is_admin' => $isAdmin,
                //     'position' => $userData['position'],
                //     'department' => $userData['department'],
                // ]);
                
                return $userData;
            })->filter(function ($userData) {
                return !empty($userData['id']) && !empty($userData['email']);
            })->values();

            $approvers = $assignableUsers->filter(function ($userData) {
                return $userData['is_admin'] === true;
            })->map(function ($approver) {
                $approverData = [
                    'id' => $approver['id'],
                    'name' => $approver['name'],
                    'email' => $approver['email'],
                    'first_name' => $approver['first_name'],
                    'last_name' => $approver['last_name'],
                    'position' => $approver['position'],
                    'department' => $approver['department'],
                    'role' => $approver['role'],
                    'is_admin' => $approver['is_admin'],
                ];
                
                // // Log each approver with their role details
                // Log::info('TICKET_CONTROLLER: Mapped approver', [
                //     'approver_id' => $approverData['id'],
                //     'name' => $approverData['name'],
                //     'email' => $approverData['email'],
                //     'role' => $approverData['role'],
                //     'is_admin' => $approverData['is_admin'],
                //     'position' => $approverData['position'],
                //     'department' => $approverData['department'],
                // ]);
                
                return $approverData;
            })->values();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log::info('TICKET_CONTROLLER: Users fetched successfully', [
            //     'total_users' => $assignableUsers->count(),
            //     'approvers_count' => $approvers->count(),
            //     'execution_time_ms' => $executionTime,
            // ]);

            return response()->json([
                'assignable_users' => $assignableUsers,
                'approvers' => $approvers
            ]);
            
        } catch (\Exception $e) {
            Log::error('TICKET_CONTROLLER: Failed to fetch users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? null,
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
                'assignable_users' => [],
                'approvers' => []
            ], 500);
        }
    }

    /**
     * Get ONLY approvers (for backward compatibility)
     */
    public function getApprovers(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found',
                    'approvers' => []
                ], 404);
            }

            $response = $this->getAssignableUsers($request);
            $data = json_decode($response->getContent(), true);

            return response()->json([
                'approvers' => $data['approvers'] ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('TICKET_CONTROLLER: Failed to fetch approvers', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch approvers',
                'error' => $e->getMessage(),
                'approvers' => []
            ], 500);
        }
    }

    /**
     * Show single ticket details
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();
       
        if ($user->hasRole('admin')) {
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
           
            $ticketUserEmployee = $ticket->user->employee;
            if (!$ticketUserEmployee || $ticketUserEmployee->business_id !== $employee->business_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($ticket->user_id !== $user->id) {
            if (!$ticket->assignedUsers()->where('user_id', $user->id)->exists()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        
        $ticketData = $ticket->load(['user', 'approver', 'assignedUsers'])->toArray();
       
        if (isset($ticketData['approver'])) {
            $ticketData['approver']['name'] = trim(
                ($ticketData['approver']['first_name'] ?? '') . ' ' .
                ($ticketData['approver']['last_name'] ?? '')
            );
        }
       
        if (isset($ticketData['user'])) {
            $ticketData['user']['name'] = trim(
                ($ticketData['user']['first_name'] ?? '') . ' ' .
                ($ticketData['user']['last_name'] ?? '')
            );
        }
        
        return response()->json($ticketData);
    }

    /**
     * Update ticket
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
       
        if ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
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
            'category' => 'sometimes|string|max:100',
            'subcategory' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->update($request->only(['title', 'description', 'priority', 'due_date', 'category', 'subcategory']));
       
        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => $ticket->load(['user', 'approver', 'assignedUsers'])
        ]);
    }

    /**
     * Delete ticket
     */
    public function destroy(Ticket $ticket)
    {
        $user = Auth::user();
       
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
     * Get user's own tickets
     */
    public function myTickets(Request $request)
    {
        $user = Auth::user();
       
        $query = Ticket::where('user_id', $user->id)
            ->with(['approver', 'assignedUsers']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $tickets = $query->latest()->paginate(10);
       
        return response()->json($tickets);
    }

    /**
     * Get tickets for admin approval
     * UPDATED: Shows all pending tickets from the business, not just assigned to specific admin
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
        
        // Show ALL tickets from the business that need admin action
        $query = Ticket::with(['user', 'assignedUsers', 'approver'])
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
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $tickets = $query->latest()->paginate(10);
       
        return response()->json($tickets);
    }

    public function count(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::query();
        
        if ($user->hasRole('admin')) {
            $employee = $user->employee;
            if ($employee) {
                $query->whereHas('user.employee', function ($q) use ($employee) {
                    $q->where('business_id', $employee->business_id);
                });
            }
        } elseif (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json([
            'count' => $query->count()
        ]);
    }

    /**
     * Update ticket priority
     * UPDATED: Any admin from the same business can update priority
     */
    public function updatePriority(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can update ticket priority'], 403);
        }
        
        // Verify admin is from the same business
        $adminEmployee = $user->employee;
        $ticketUserEmployee = $ticket->user->employee;
        
        if (!$adminEmployee || !$ticketUserEmployee || 
            $adminEmployee->business_id !== $ticketUserEmployee->business_id) {
            return response()->json(['message' => 'You can only update tickets from your business'], 403);
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
        
        Log::info('Ticket priority updated', [
            'ticket_id' => $ticket->id,
            'old_priority' => $oldPriority,
            'new_priority' => $request->priority,
            'updated_by' => $user->id,
            'reason' => $request->update_reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket priority updated successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }

    /**
     * Reassign ticket to another approver
     * UPDATED: Any admin from the same business can reassign
     */
    public function reassignTicket(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can reassign tickets'], 403);
        }
        
        // Verify admin is from the same business
        $adminEmployee = $user->employee;
        $ticketUserEmployee = $ticket->user->employee;
        
        if (!$adminEmployee || !$ticketUserEmployee || 
            $adminEmployee->business_id !== $ticketUserEmployee->business_id) {
            return response()->json(['message' => 'You can only reassign tickets from your business'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'new_approver_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newApprover = User::with('employee')->find($request->new_approver_id);
        
        if (!$newApprover->hasRole('admin')) {
            return response()->json([
                'message' => 'New approver must be an admin.'
            ], 422);
        }
        
        $newApproverEmployee = $newApprover->employee;
        
        if (!$newApproverEmployee || $newApproverEmployee->business_id !== $adminEmployee->business_id) {
            return response()->json([
                'message' => 'New approver must be from the same business.'
            ], 422);
        }

        $oldApproverId = $ticket->approver_id;
        
        $ticket->update([
            'approver_id' => $request->new_approver_id,
        ]);
        
        try {
            Mail::to($newApprover->email)->send(new TicketApprovalRequest($ticket));
        } catch (\Exception $e) {
            Log::warning('Failed to send reassignment email', [
                'ticket_id' => $ticket->id,
                'new_approver_email' => $newApprover->email,
                'error' => $e->getMessage()
            ]);
        }
        
        Log::info('Ticket reassigned', [
            'ticket_id' => $ticket->id,
            'old_approver_id' => $oldApproverId,
            'new_approver_id' => $request->new_approver_id,
            'reassigned_by' => $user->id,
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket reassigned successfully',
            'ticket' => $ticket->load(['user', 'approver'])
        ]);
    }

    /**
     * Helper method to log ticket activities
     */
    private function logActivity(Ticket $ticket, $action, $description, $user = null)
    {
        if (!class_exists(\App\Models\TicketActivity::class)) {
            return;
        }
        
        try {
            $ticket->activities()->create([
                'user_id' => $user ? $user->id : Auth::id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ticket activity', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * Get ticket comments
     */
    public function getComments(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Check permissions
        if (!$this->canViewTicket($ticket, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $ticket->comments()->with(['user', 'attachments']);
        
        // For non-admins, only show public comments
        if (!$user->hasRole('admin')) {
            $query->where('is_internal', false);
        }
        
        $comments = $query->orderBy('created_at', 'asc')->get();
        
        return response()->json($comments);
    }

    /**
 * Add comment to ticket
 */
public function addComment(Request $request, Ticket $ticket)
{
    $user = Auth::user();
    
    // Check permissions
    if (!$this->canAddComment($ticket, $user)) {
        return response()->json(['message' => 'Unauthorized to comment on this ticket'], 403);
    }

    $validator = Validator::make($request->all(), [
        'content' => 'required|string|max:5000',
        'is_internal' => 'nullable|in:0,1,true,false', // Changed to accept string values
        'attachments' => 'nullable|array|max:5',  // Added nullable
        'attachments.*' => 'file|max:10240', // 10MB max per file
    ]);

    if ($validator->fails()) {
        Log::error('Comment validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $request->all(),
        ]);
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();
        
        // Create comment - convert is_internal to boolean
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $request->input('content'),
            'is_internal' => filter_var($request->input('is_internal', false), FILTER_VALIDATE_BOOLEAN),
        ]);

        // Handle attachments
        $attachmentIds = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachment = $this->storeAttachment($file, $ticket, $user, $comment);
                $attachmentIds[] = $attachment->id;
            }
        }

        // Log activity
        $this->logActivity($ticket, 'commented', "Added a comment", $user);

        // Update ticket's updated_at timestamp
        $ticket->touch();
// ✨ NEW: Send notification (only for non-internal comments)
        if (!$comment->is_internal) {
            $this->notificationService->notifyCommentAdded($ticket, $comment);
        }

        DB::commit();

        // Load relationships
        $comment->load(['user', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => $comment,
            'attachment_count' => count($attachmentIds),
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Failed to add comment', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to add comment: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Update comment
     */
    public function updateComment(Request $request, Ticket $ticket, TicketComment $comment)
    {
        $user = Auth::user();
        
        // Check permissions
        if ($comment->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized to update this comment'], 403);
        }
        
        // Check if comment belongs to ticket
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Comment does not belong to this ticket'], 404);
        }
        
        // Check edit timeout for non-admins
        if (!$user->hasRole('admin') && $comment->created_at->diffInMinutes(now()) > 15) {
            return response()->json(['message' => 'Comments can only be edited within 15 minutes'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $comment->update([
                'content' => $request->input('content'),
            ]);

            // Log activity
            $this->logActivity($ticket, 'comment_updated', "Updated a comment", $user);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'comment' => $comment->fresh(['user', 'attachments']),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update comment', [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
            ], 500);
        }
    }

    /**
     * Delete comment
     */
    public function deleteComment(Request $request, Ticket $ticket, TicketComment $comment)
    {
        $user = Auth::user();
        
        // Check permissions
        if ($comment->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized to delete this comment'], 403);
        }
        
        // Check if comment belongs to ticket
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Comment does not belong to this ticket'], 404);
        }

        try {
            $comment->delete();

            // Log activity
            $this->logActivity($ticket, 'comment_deleted', "Deleted a comment", $user);

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete comment', [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
            ], 500);
        }
    }

    /**
     * Get ticket attachments
     */
    public function getAttachments(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Check permissions
        if (!$this->canViewTicket($ticket, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attachments = $ticket->attachments()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($attachments);
    }

    /**
 * Upload attachment to ticket
 */
public function uploadAttachment(Request $request, Ticket $ticket)
{
    // FIXED: Changed $ticketId to $ticket->id
    Log::info('Upload attempt', [
        'user_id' => auth()->id(),
        'ticket_id' => $ticket->id,  // <-- This was the bug
        'has_file' => $request->hasFile('file'),
        'auth_check' => auth()->check()
    ]);
    
    $user = Auth::user();
    
    // Check permissions
    if (!$this->canAddAttachment($ticket, $user)) {
        return response()->json(['message' => 'Unauthorized to add attachments'], 403);
    }

    $validator = Validator::make($request->all(), [
        'file' => 'required|file|max:10240', // 10MB max
        'comment_id' => 'nullable|exists:ticket_comments,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $file = $request->file('file');
        $attachment = $this->storeAttachment($file, $ticket, $user, $request->comment_id);

        // Log activity
        $this->logActivity($ticket, 'attachment_added', "Added attachment: {$attachment->original_name}", $user);

        // Update ticket's updated_at timestamp
        $ticket->touch();
// ✨ NEW: Send notification
        $this->notificationService->notifyAttachmentUploaded($ticket, $attachment);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment->load('user'),
        ]);
        
    } catch (\Exception $e) {
        Log::error('Failed to upload attachment', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to upload attachment: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Delete attachment
     */
    public function deleteAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        $user = Auth::user();
        
        // Check permissions
        if ($attachment->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized to delete this attachment'], 403);
        }
        
        // Check if attachment belongs to ticket
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Attachment does not belong to this ticket'], 404);
        }

        try {
            // Delete file from storage
            Storage::disk($attachment->disk)->delete($attachment->path);
            
            // Delete database record
            $attachmentName = $attachment->original_name;
            $attachment->delete();

            // Log activity
            $this->logActivity($ticket, 'attachment_deleted', "Deleted attachment: {$attachmentName}", $user);

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete attachment', [
                'attachment_id' => $attachment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
            ], 500);
        }
    }

    /**
     * Download attachment
     */
    public function downloadAttachment(TicketAttachment $attachment)
    {
        $user = Auth::user();
        $ticket = $attachment->ticket;
        
        // Check permissions
        if (!$this->canViewTicket($ticket, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $filePath = Storage::disk($attachment->disk)->path($attachment->path);
        
        return response()->download($filePath, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    /**
     * Get ticket activity history
     */
    public function getActivityHistory(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        
        // Check permissions
        if (!$this->canViewTicket($ticket, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activities = $ticket->activities()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json($activities);
    }

    /**
     * Helper method to store attachment
     */
    private function storeAttachment($file, $ticket, $user, $comment = null)
    {
        $uuid = Str::uuid();
        $originalName = $file->getClientOriginalName();
        $fileName = $uuid . '.' . $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        
        // Create directory if not exists
        $directory = 'tickets/' . $ticket->id;
        $path = $file->storeAs($directory, $fileName, 'public');
        
        // Create attachment record
        $attachment = TicketAttachment::create([
            'uuid' => $uuid,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment_id' => $comment instanceof TicketComment ? $comment->id : $comment,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'path' => $path,
            'disk' => 'public',
            'size' => $size,
            'meta' => [
                'extension' => $file->getClientOriginalExtension(),
                'uploaded_via' => 'web',
            ],
        ]);
        
        return $attachment;
    }

    /**
     * Helper method to check if user can view ticket
     */
    private function canViewTicket(Ticket $ticket, $user): bool
    {
        if ($user->hasRole('admin')) {
            $employee = $user->employee;
            $ticketUserEmployee = $ticket->user->employee;
            
            if ($employee && $ticketUserEmployee && 
                $employee->business_id === $ticketUserEmployee->business_id) {
                return true;
            }
            return false;
        }
        
        return $ticket->user_id === $user->id || 
               $ticket->assignedUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * Helper method to check if user can add comment
     */
    private function canAddComment(Ticket $ticket, $user): bool
    {
        return $this->canViewTicket($ticket, $user);
    }

    /**
     * Helper method to check if user can add attachment
     */
    private function canAddAttachment(Ticket $ticket, $user): bool
    {
        return $this->canViewTicket($ticket, $user);
    }
}
<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Services\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;  
class LeaveController extends Controller
{
     use AuthorizesRequests;
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }
    public function index(Request $request): AnonymousResourceCollection
{
    $user = $request->user();
    
    // Determine the context business ID
    // If user is Employee, use their business_id. If Manager/Admin, use their assigned business context.
    $contextBusinessId = $user->employee->business_id ?? $user->business_id;

    $leaves = Leave::with(['employee.user', 'manager'])
        // 1. CRITICAL: Enforce Business Tenancy
        ->whereHas('employee', function ($query) use ($contextBusinessId) {
            $query->where('business_id', $contextBusinessId);
        })
        // 2. Role specific filters
        ->when($user->isEmployee(), function ($query) use ($user) {
            return $query->where('employee_id', $user->employee->id);
        })
        ->when($user->isManager(), function ($query) use ($user) {
            return $query->where('manager_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();
       
    return LeaveResource::collection($leaves);
}
 public function store(StoreLeaveRequest $request): JsonResponse
{
     // Ensure we load the employee AND their business relationship
    $employee = $request->user()->employee()->with('business')->first();
    $type = strtolower($request->type); // Ensure lowercase
   
    // Check leave balance
    $requestedDays = $this->calculateLeaveDays($request->start_date, $request->end_date);
   
    if (!$this->leaveBalanceService->hasSufficientBalance($employee->id, $type, $requestedDays)) {
        $balance = $this->leaveBalanceService->getBalance($employee->id, $type);
        return response()->json([
            'message' => "Insufficient leave balance. You have {$balance} days available, but requested {$requestedDays} days."
        ], 422);
    }
    $leave = Leave::create([
         'employee_id' => $employee->id,
        'manager_id' => $employee->manager_id,
        'business_id' => $employee->business_id, // Store this for easier querying later
        'type' => $type,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'total_days' => $requestedDays,
        'reason' => $request->reason,
        'status' => 'pending',
    ]);
    return response()->json([
        'leave' => new LeaveResource($leave->load(['employee.user', 'manager'])),
        'message' => 'Leave application submitted successfully'
    ], 201);
}
 public function approve(ApproveLeaveRequest $request, Leave $leave): JsonResponse

    {

        $this->authorize('approve', $leave);

       

        $validated = $request->validated();

        $leave->update([

            'status' => $validated['status'],

            'manager_notes' => $validated['manager_notes'] ?? null,

        ]);

        // Update leave balance if approved

        if ($validated['status'] === 'approved') {

            $this->leaveBalanceService->deductBalance(

                $leave->employee_id,

                $leave->type,

                $leave->total_days

            );

        }

        // Dispatch notification event

        event(new \App\Events\LeaveStatusUpdated($leave));

        return response()->json([

            'leave' => new LeaveResource($leave->load(['employee.user', 'manager'])),

            'message' => 'Leave application ' . $validated['status'] . ' successfully'

        ]);

    }
public function balance(Request $request): JsonResponse
{
    $employee = $request->user()->employee;
    
    // This will automatically use the latest system settings
    $balances = $this->leaveBalanceService->getAllBalances($employee->id);
   
    return response()->json([
        'balances' => $balances,
        'year' => date('Y')
    ]);
}
    public function pendingLeaves(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
       
        $leaves = Leave::with(['employee.user'])
            ->where('manager_id', $user->id)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
           
        return LeaveResource::collection($leaves);
    }
    private function calculateLeaveDays($startDate, $endDate): int
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
       
        return $start->diffInDays($end) + 1;
    }
   /**
 * Get leaves for current month (for admin/HR dashboard)
 */
public function currentMonthLeaves(Request $request): JsonResponse
{
    try {
        $currentUser = $request->user();
        $businessId = $request->query('business_id');
        
        // Use business_id from query or user's current business
        $filterBusinessId = $businessId ?: $currentUser->current_business_id;
        
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
        ]);
        
        // Use provided dates or default to current month
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();
           
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();
        
        Log::info('LEAVE_CONTROLLER: Fetching current month leaves', [
            'user_id' => $currentUser->id,
            'business_id' => $filterBusinessId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString()
        ]);
        
        // Build query with business scoping
        $leaveQuery = Leave::with([
                'employee.user',
                'manager'
            ])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            });
        
        // Apply business filter if specified
        if ($filterBusinessId) {
            $leaveQuery->whereHas('employee.user', function($q) use ($filterBusinessId) {
                $q->where('current_business_id', $filterBusinessId);
            });
        }
        
        $allLeaves = $leaveQuery->get();
        
        // Get pending leaves only
        $pendingLeaves = $allLeaves->where('status', 'pending');
        
        // Count employees currently on leave (today)
        $today = now()->toDateString();
        $onLeaveQuery = Leave::where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
            
        if ($filterBusinessId) {
            $onLeaveQuery->whereHas('employee.user', function($q) use ($filterBusinessId) {
                $q->where('current_business_id', $filterBusinessId);
            });
        }
        
        $onLeaveCount = $onLeaveQuery->count();
        
        Log::info('LEAVE_CONTROLLER: Current month leaves retrieved', [
            'total_leaves' => $allLeaves->count(),
            'pending_count' => $pendingLeaves->count(),
            'on_leave_today' => $onLeaveCount
        ]);
        
        // Return data in format expected by dashboard
        return response()->json([
            'pending_leaves' => $pendingLeaves->map(function($leave) {
                return [
                    'id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'employee' => [
                        'id' => $leave->employee->id,
                        'first_name' => $leave->employee->user->first_name ?? '',
                        'last_name' => $leave->employee->user->last_name ?? '',
                        'name' => trim(($leave->employee->user->first_name ?? '') . ' ' . ($leave->employee->user->last_name ?? '')),
                    ],
                    'leave_type' => $leave->leave_type,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'days' => $leave->days ?? 0,
                    'created_at' => $leave->created_at->toDateTimeString(),
                ];
            })->values(),
            'pending_count' => $pendingLeaves->count(),
            'on_leave_count' => $onLeaveCount,
            'total_leaves_this_month' => $allLeaves->count(),
            'approved_count' => $allLeaves->where('status', 'approved')->count(),
            'rejected_count' => $allLeaves->where('status', 'rejected')->count(),
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('LEAVE_CONTROLLER: Failed to fetch current month leaves', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to fetch leave data',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Cancel a pending leave request (employee self-cancel)
     */
    public function cancel(Request $request, Leave $leave): JsonResponse
    {
        try {
            // Authorization: Ensure the leave belongs to the authenticated employee
            $employee = $request->user()->employee;
            if (!$employee || $leave->employee_id !== $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only cancel your own leave requests.'
                ], 403);
            }
            // Check if leave can be cancelled (only pending/under_review)
            if (!in_array($leave->status, ['pending', 'under_review'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This leave request cannot be cancelled. It has already been processed.'
                ], 422);
            }
            // Update status to cancelled
            $leave->status = 'cancelled';
            $leave->cancelled_at = now(); // Optional: Add a timestamp field to model/migration
            $leave->cancelled_by = $employee->id; // Optional: Track who cancelled
            $leave->save();
            // Optional: Notify manager (if you have notifications)
            // Notification::send($leave->manager, new LeaveCancelled($leave));
            Log::info('LEAVE_CONTROLLER: Leave cancelled successfully', [
                'user_id' => $request->user()->id,
                'employee_id' => $employee->id,
                'leave_id' => $leave->id,
                'original_status' => $leave->getOriginal('status'),
                'cancelled_at' => $leave->cancelled_at
            ]);
            return response()->json([
                'success' => true,
                'leave' => $leave, // Or use a LeaveResource
                'message' => 'Leave request has been cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel leave', [
                'user_id' => $request->user()->id ?? null,
                'leave_id' => $leave->id ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel leave request: ' . $e->getMessage()
            ], 500);
        }
    }
        /**
     * Reject a leave request (Manager only)
     */
    public function reject(Request $request, Leave $leave): JsonResponse
    {
        $this->authorize('approve', $leave); // Using same policy as approve
        
        $validated = $request->validate([
            'manager_notes' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500', // Alternative field name
        ]);
        
        $leave->update([
            'status' => 'rejected',
            'manager_notes' => $validated['manager_notes'] ?? $validated['reason'] ?? null,
        ]);
        
        // Dispatch notification event
        event(new \App\Events\LeaveStatusUpdated($leave));
        
        Log::info('LEAVE_CONTROLLER: Leave rejected', [
            'leave_id' => $leave->id,
            'manager_id' => $request->user()->id,
            'employee_id' => $leave->employee_id
        ]);
        
        return response()->json([
            'leave' => new LeaveResource($leave->load(['employee.user', 'manager'])),
            'message' => 'Leave application rejected successfully'
        ]);
    }
}
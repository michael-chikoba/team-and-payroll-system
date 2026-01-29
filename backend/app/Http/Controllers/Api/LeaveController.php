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
        
        $contextBusinessId = $user->employee->business_id ?? $user->business_id;

        $leaves = Leave::with(['employee.user', 'manager'])
            ->whereHas('employee', function ($query) use ($contextBusinessId) {
                $query->where('business_id', $contextBusinessId);
            })
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
        $employee = $request->user()->employee()->with('business')->first();
        $type = strtolower($request->type);
       
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
            'business_id' => $employee->business_id,
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
        $user = $request->user();
        
        if (!$user->isManager() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only managers and admins can approve leaves.'
            ], 403);
        }
        
        if ($user->isManager() && !$user->isAdmin() && $leave->manager_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only approve leaves assigned to you.'
            ], 403);
        }
        
        $validated = $request->validated();
        $leave->update([
            'status' => $validated['status'],
            'manager_notes' => $validated['manager_notes'] ?? null,
        ]);
        
        if ($validated['status'] === 'approved') {
            $this->leaveBalanceService->deductBalance(
                $leave->employee_id,
                $leave->type,
                $leave->total_days
            );
        }
        
        event(new \App\Events\LeaveStatusUpdated($leave));
        
        return response()->json([
            'leave' => new LeaveResource($leave->load(['employee.user', 'manager'])),
            'message' => 'Leave application ' . $validated['status'] . ' successfully'
        ]);
    }

    public function balance(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        
        $balances = $this->leaveBalanceService->getAllBalances($employee->id);
       
        return response()->json([
            'balances' => $balances,
            'year' => date('Y')
        ]);
    }

    public function pendingLeaves(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $contextBusinessId = $this->getContextBusinessId($user);

        $leaves = Leave::with(['employee.user','manager'])
            ->whereHas('employee', function ($query) use ($contextBusinessId) {
                $query->where('business_id', $contextBusinessId);
            })
            ->when($user->isManager() && !$user->isAdmin(), function ($query) use ($user) {
                return $query->where('manager_id', $user->id);
            })
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
     * Get leaves for current month (for admin/manager dashboard)
     * Returns ALL leaves (not just pending)
     */
    public function currentMonthLeaves(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            if (!$currentUser->isManager() && !$currentUser->isAdmin()) {
                Log::warning('LEAVE_CONTROLLER: Unauthorized access attempt', [
                    'user_id' => $currentUser->id,
                    'role' => $currentUser->role
                ]);
                
                return response()->json([
                    'message' => 'Unauthorized. Only managers and admins can access this endpoint.'
                ], 403);
            }
            
            $contextBusinessId = $this->getContextBusinessId($currentUser);
            
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
                'user_role' => $currentUser->role,
                'business_id' => $contextBusinessId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);
            
            // Build query with business scoping - ALL LEAVES
            $leaveQuery = Leave::with([
                    'employee.user',
                    'employee.business',
                    'manager'
                ])
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->whereHas('employee', function ($query) use ($contextBusinessId) {
                    $query->where('business_id', $contextBusinessId);
                });
            
            // For managers (non-admin), filter by manager_id
            if ($currentUser->isManager() && !$currentUser->isAdmin()) {
                $leaveQuery->where('manager_id', $currentUser->id);
            }
            
            // Get ALL leaves (not just pending)
            $allLeaves = $leaveQuery->orderBy('created_at', 'desc')->get();
            
            // Separate pending leaves for backward compatibility
            $pendingLeaves = $allLeaves->where('status', 'pending');
            $approvedLeaves = $allLeaves->where('status', 'approved');
            $rejectedLeaves = $allLeaves->where('status', 'rejected');
            
            // Count employees currently on leave (today)
            $today = now()->toDateString();
            $onLeaveQuery = Leave::with(['employee.user'])
                ->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->whereHas('employee', function ($query) use ($contextBusinessId) {
                    $query->where('business_id', $contextBusinessId);
                });
                
            if ($currentUser->isManager() && !$currentUser->isAdmin()) {
                $onLeaveQuery->where('manager_id', $currentUser->id);
            }
            
            $onLeaveCount = $onLeaveQuery->count();
            $onLeaveEmployees = $onLeaveQuery->get();
            
            // Helper function to format leave data
            $formatLeave = function($leave) {
                return [
                    'id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'employee' => [
                        'id' => $leave->employee->id,
                        'first_name' => $leave->employee->user->first_name ?? '',
                        'last_name' => $leave->employee->user->last_name ?? '',
                        'name' => trim(($leave->employee->user->first_name ?? '') . ' ' . ($leave->employee->user->last_name ?? '')),
                        'department' => $leave->employee->department ?? 'N/A',
                        'business' => [
                            'id' => $leave->employee->business->id ?? null,
                            'name' => $leave->employee->business->name ?? 'N/A',
                        ],
                    ],
                    'type' => $leave->type,
                    'leave_type' => $leave->type,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'total_days' => $leave->total_days,
                    'days' => $leave->total_days ?? 0,
                    'manager_notes' => $leave->manager_notes,
                    'created_at' => $leave->created_at->toDateTimeString(),
                ];
            };
            
            Log::info('LEAVE_CONTROLLER: Current month leaves retrieved', [
                'total_leaves' => $allLeaves->count(),
                'pending_count' => $pendingLeaves->count(),
                'approved_count' => $approvedLeaves->count(),
                'rejected_count' => $rejectedLeaves->count(),
                'on_leave_today' => $onLeaveCount
            ]);
            
            return response()->json([
                // For backward compatibility - pending leaves
                'pending_leaves' => $pendingLeaves->map($formatLeave)->values(),
                
                // NEW: All leaves (including all statuses)
                'all_leaves' => $allLeaves->map($formatLeave)->values(),
                
                // On leave today employees
                'on_leave_employees' => $onLeaveEmployees->map(function($leave) {
                    return [
                        'id' => $leave->employee->id,
                        'name' => trim(($leave->employee->user->first_name ?? '') . ' ' . ($leave->employee->user->last_name ?? '')),
                        'department' => $leave->employee->department ?? 'N/A',
                        'leave_type' => $leave->type,
                        'start_date' => $leave->start_date,
                        'end_date' => $leave->end_date,
                        'days_remaining' => Carbon::parse($leave->end_date)->diffInDays(Carbon::now()) + 1,
                    ];
                })->values(),
                
                // Statistics
                'pending_count' => $pendingLeaves->count(),
                'on_leave_count' => $onLeaveCount,
                'total_leaves_this_month' => $allLeaves->count(),
                'approved_count' => $approvedLeaves->count(),
                'rejected_count' => $rejectedLeaves->count(),
                
                // Additional status counts
                'status_counts' => [
                    'pending' => $pendingLeaves->count(),
                    'approved' => $approvedLeaves->count(),
                    'rejected' => $rejectedLeaves->count(),
                    'cancelled' => $allLeaves->where('status', 'cancelled')->count(),
                ],
                
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
     * Reject a leave request (Manager only)
     */
    public function reject(Request $request, Leave $leave): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->isManager() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only managers and admins can reject leaves.'
            ], 403);
        }
        
        if ($user->isManager() && !$user->isAdmin() && $leave->manager_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only reject leaves assigned to you.'
            ], 403);
        }
        
        $validated = $request->validate([
            'manager_notes' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);
        
        $leave->update([
            'status' => 'rejected',
            'manager_notes' => $validated['manager_notes'] ?? $validated['reason'] ?? null,
        ]);
        
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

    /**
     * Get all leaves (with filters) - Admin/Manager endpoint
     */
    public function allLeaves(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $contextBusinessId = $this->getContextBusinessId($currentUser);

            if (!$currentUser->isAdmin() && !$currentUser->isManager()) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $request->validate([
                'status' => 'sometimes|in:pending,approved,rejected,cancelled',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
                'employee_id' => 'sometimes|integer',
            ]);

            $query = Leave::with(['employee.user', 'employee.business', 'manager'])
                ->whereHas('employee', function ($q) use ($contextBusinessId) {
                    $q->where('business_id', $contextBusinessId);
                });

            if ($currentUser->isManager() && !$currentUser->isAdmin()) {
                $query->where('manager_id', $currentUser->id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->where(function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
                });
            }

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            $leaves = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'data' => $leaves->map(function($leave) {
                    return [
                        'id' => $leave->id,
                        'employee_id' => $leave->employee_id,
                        'employee' => [
                            'id' => $leave->employee->id,
                            'user' => [
                                'name' => trim(($leave->employee->user->first_name ?? '') . ' ' . ($leave->employee->user->last_name ?? '')),
                                'first_name' => $leave->employee->user->first_name ?? '',
                                'last_name' => $leave->employee->user->last_name ?? '',
                            ],
                            'department' => $leave->employee->department ?? 'N/A',
                        ],
                        'type' => $leave->type,
                        'start_date' => $leave->start_date,
                        'end_date' => $leave->end_date,
                        'total_days' => $leave->total_days,
                        'reason' => $leave->reason,
                        'status' => $leave->status,
                        'manager_notes' => $leave->manager_notes,
                        'created_at' => $leave->created_at,
                        'updated_at' => $leave->updated_at,
                    ];
                }),
                'count' => $leaves->count()
            ]);

        } catch (\Exception $e) {
            Log::error('LEAVE_CONTROLLER: Failed to fetch all leaves', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to fetch leaves',
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
            $employee = $request->user()->employee;
            if (!$employee || $leave->employee_id !== $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only cancel your own leave requests.'
                ], 403);
            }
            
            if (!in_array($leave->status, ['pending', 'under_review'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This leave request cannot be cancelled. It has already been processed.'
                ], 422);
            }
            
            $leave->status = 'cancelled';
            $leave->cancelled_at = now();
            $leave->cancelled_by = $employee->id;
            $leave->save();
            
            Log::info('LEAVE_CONTROLLER: Leave cancelled successfully', [
                'user_id' => $request->user()->id,
                'employee_id' => $employee->id,
                'leave_id' => $leave->id,
                'original_status' => $leave->getOriginal('status'),
                'cancelled_at' => $leave->cancelled_at
            ]);
            
            return response()->json([
                'success' => true,
                'leave' => $leave,
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
     * Helper: Get the context business ID for the current user
     */
    private function getContextBusinessId($user): ?int
    {
        if ($user->isEmployee() && $user->employee) {
            return $user->employee->business_id;
        }
        
        return $user->current_business_id ?? $user->business_id;
    }
}
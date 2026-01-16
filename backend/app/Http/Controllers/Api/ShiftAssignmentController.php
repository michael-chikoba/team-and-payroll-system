<?php

namespace App\Http\Controllers\Api;

use App\Models\ShiftAssignment;
use App\Models\Employee;
use App\Services\ShiftAssignmentService;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftAssignmentController extends Controller
{


     protected ShiftAssignmentService $shiftService;
    protected LeaveBalanceService $leaveService;

    public function __construct(
        ShiftAssignmentService $shiftService,
        LeaveBalanceService $leaveService
    ) {
        $this->shiftService = $shiftService;
        $this->leaveService = $leaveService;
    }

    
    /**
     * Normalize time format helper
     */
    private function normalizeTime(string $time): string
    {
        // If time is in H:i format, add seconds
        if (strlen($time) === 5 && substr_count($time, ':') === 1) {
            return $time . ':00';
        }
        // If already in H:i:s format, return as is
        return $time;
    }

    /**
     * Get available employees for shift assignment
     * GET /api/shifts/available-employees?date=2025-12-26
     */
    public function getAvailableEmployees(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'business_id' => 'nullable|integer|exists:businesses,id',
                'department_id' => 'nullable|integer|exists:departments,id'
            ]);

            $result = $this->shiftService->getAvailableEmployees(
                $request->date,
                $request->business_id,
                $request->department_id
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get available employees', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign shift to employee
     * POST /api/shifts/assign
     */
    public function assignShift(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|integer|exists:employees,id',
                'shift_date' => 'required|date',
                'shift_type' => 'required|string|in:morning,day,night',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'notes' => 'nullable|string|max:500',
                'business_id' => 'nullable|integer|exists:businesses,id',
                'country_id' => 'nullable|integer|exists:countries,id',
                'department_id' => 'nullable|integer|exists:departments,id',
            ]);

            // Normalize times to H:i:s format
            $validated['start_time'] = $this->normalizeTime($validated['start_time']);
            $validated['end_time'] = $this->normalizeTime($validated['end_time']);

            $validated['assigned_by'] = $request->user()->employee->id ?? $request->user()->id;

            Log::info('Assigning shift with times', [
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time']
            ]);

            $result = $this->shiftService->assignShift($validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign shift',
                    'errors' => $result['errors']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $result['shift'],
                'message' => $result['message']
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to assign shift', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign shifts
     * POST /api/shifts/bulk-assign
     */
    public function bulkAssignShifts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'required|integer|exists:employees,id',
                'shift_date' => 'required|date',
                'shift_type' => 'required|string|in:morning,day,night',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'notes' => 'nullable|string|max:500',
            ]);

            // Normalize times
            $validated['start_time'] = $this->normalizeTime($validated['start_time']);
            $validated['end_time'] = $this->normalizeTime($validated['end_time']);

            $assignedBy = $request->user()->employee->id ?? $request->user()->id;

            $shiftData = [
                'shift_date' => $validated['shift_date'],
                'shift_type' => $validated['shift_type'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'notes' => $validated['notes'] ?? null,
            ];

            $results = $this->shiftService->bulkAssignShifts(
                $validated['employee_ids'],
                $shiftData,
                $assignedBy
            );

            return response()->json([
                'success' => true,
                'message' => "Assigned {$results['total']} shifts",
                'results' => [
                    'total' => $results['total'],
                    'success' => count($results['success']),
                    'failed' => count($results['failed']),
                    'details' => $results
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk shift assignment failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Accept shift
     * POST /api/shifts/{shift}/accept
     */
    public function acceptShift(Request $request, int $shiftId): JsonResponse
    {
        try {
            $employee = $request->user()->employee;
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $result = $this->shiftService->acceptShift($shiftId, $employee->id);

            if (!$result['success']) {
                return response()->json($result, 422);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to accept shift', [
                'shift_id' => $shiftId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject shift
     * POST /api/shifts/{shift}/reject
     */
    public function rejectShift(Request $request, int $shiftId): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $employee = $request->user()->employee;
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $result = $this->shiftService->rejectShift(
                $shiftId,
                $employee->id,
                $request->reason
            );

            if (!$result['success']) {
                return response()->json($result, 422);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to reject shift', [
                'shift_id' => $shiftId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's assigned shifts
     * GET /api/shifts/my-shifts?start_date=2025-12-01&end_date=2025-12-31
     */
    public function getMyShifts(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $query = ShiftAssignment::where('employee_id', $employee->id);

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('shift_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            } else {
                // Default to current month
                $query->whereMonth('shift_date', now()->month)
                      ->whereYear('shift_date', now()->year);
            }

            $shifts = $query->orderBy('shift_date', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $shifts,
                'summary' => [
                    'total' => $shifts->count(),
                    'pending' => $shifts->where('status', 'pending')->count(),
                    'accepted' => $shifts->where('status', 'accepted')->count(),
                    'rejected' => $shifts->where('status', 'rejected')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get employee shifts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if employee can be assigned shift
     * GET /api/shifts/check-availability?employee_id=55&date=2025-12-26
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'employee_id' => 'required|integer|exists:employees,id',
                'date' => 'required|date'
            ]);

            $canAssign = $this->leaveService->canAssignShift(
                $request->employee_id,
                $request->date
            );

            $result = [
                'success' => true,
                'can_assign' => $canAssign,
                'date' => $request->date,
                'employee_id' => $request->employee_id
            ];

            if (!$canAssign) {
                $result['reason'] = $this->leaveService->getShiftBlockReason(
                    $request->employee_id,
                    $request->date
                );
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to check availability', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
  public function index(Request $request)
    {
        $user = auth()->user();
        
        Log::info('=== Shift Assignment Index Called ===', [
            'user_id' => $user->id,
            'request_params' => $request->all()
        ]);

        $query = ShiftAssignment::with([
            'employee.user', 
            'assignedBy',
            'department'
        ]);

        $showAll = $request->has('show_all') && $request->show_all == 1;
        
        $isSuperAdmin = $user->hasRole('super-admin');
        $isAdmin = $user->hasRole('admin');
        $isManager = $user->hasRole('manager');
        $isEmployee = $user->employee ? true : false;
        
        $assignmentsAssignedByUser = ShiftAssignment::where('assigned_by', $user->id)->count();

        if ($isSuperAdmin) {
            if ($request->has('business_id')) {
                $query->where('business_id', $request->business_id);
            }
        } 
        elseif ($isAdmin) {
            $adminEmployee = $user->employee;
            if ($adminEmployee && $adminEmployee->business_id) {
                $query->where('business_id', $adminEmployee->business_id);
            } else {
                $query->where('assigned_by', $user->id);
            }
        }
        elseif ($isManager) {
            $managerEmployee = $user->employee;
            
            if ($managerEmployee) {
                $query->where(function($q) use ($managerEmployee, $user) {
                    $q->where('assigned_by', $user->id);
                    
                    if ($managerEmployee->business_id) {
                        $q->orWhere('business_id', $managerEmployee->business_id);
                    }
                    
                    if ($managerEmployee->department_id) {
                        $q->orWhere('department_id', $managerEmployee->department_id);
                    }
                });
            } else {
                $query->where('assigned_by', $user->id);
            }
        } 
        else {
            if ($assignmentsAssignedByUser > 0) {
                $query->where(function($q) use ($user) {
                    $q->where('assigned_by', $user->id);
                    
                    if ($user->employee) {
                        $q->orWhere('employee_id', $user->employee->id);
                    }
                });
            } elseif ($isEmployee) {
                $query->where('employee_id', $user->employee->id);
            } else {
                return response()->json([
                    'assignments' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => 50,
                        'current_page' => 1,
                        'last_page' => 1,
                        'from' => 0,
                        'to' => 0,
                    ]
                ]);
            }
        }

        if (!$showAll) {
            if (!$request->filled('from_date') && !$request->filled('to_date')) {
                if ($isSuperAdmin || $isAdmin || $isManager) {
                    $query->whereDate('shift_date', '>=', Carbon::today()->subDays(30));
                } else {
                    $query->whereDate('shift_date', '>=', Carbon::today());
                }
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }
        
        if ($request->filled('employee_name')) {
            $query->whereHas('employee.user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->employee_name . '%')
                  ->orWhere('email', 'like', '%' . $request->employee_name . '%');
            });
        }
        
        if ($request->filled('from_date')) {
            $query->whereDate('shift_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('shift_date', '<=', $request->to_date);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $perPage = $request->input('per_page', 50);
        $assignments = $query->orderBy('shift_date', 'desc')
                            ->orderBy('start_time', 'asc')
                            ->paginate($perPage);

        return response()->json([
            'assignments' => $assignments->items(),
            'pagination' => [
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'from' => $assignments->firstItem(),
                'to' => $assignments->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_date' => 'sometimes|date',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'use_date_range' => 'sometimes|boolean',
            'shift_type' => 'required|in:day,night,evening,morning',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $employee = Employee::findOrFail($request->employee_id);
            
            // Normalize times
            $startTime = $this->normalizeTime($request->start_time);
            $endTime = $this->normalizeTime($request->end_time);
            
            $useDateRange = $request->boolean('use_date_range') && 
                           $request->has('start_date') && 
                           $request->has('end_date');
            
            if ($useDateRange) {
                return $this->createDateRangeAssignments($request, $user, $employee, $startTime, $endTime);
            } else {
                return $this->createSingleAssignment($request, $user, $employee, $startTime, $endTime);
            }

        } catch (\Exception $e) {
            Log::error('=== Shift Assignment Failed ===', [
                'error_message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Failed to assign shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createSingleAssignment(Request $request, $user, $employee, $startTime, $endTime)
    {
        $shiftDate = $request->shift_date ?? $request->start_date;
        
        $existing = ShiftAssignment::where('employee_id', $request->employee_id)
            ->whereDate('shift_date', $shiftDate)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Employee already has a shift assigned for this date'
            ], 400);
        }

        $assignmentData = [
            'employee_id' => $request->employee_id,
            'assigned_by' => $user->id,
            'shift_date' => $shiftDate,
            'shift_type' => $request->shift_type,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => $request->notes,
            'status' => 'pending'
        ];

        if ($employee->business_id) {
            $assignmentData['business_id'] = $employee->business_id;
        }
        if ($employee->country_id) {
            $assignmentData['country_id'] = $employee->country_id;
        }
        if ($request->department_id) {
            $assignmentData['department_id'] = $request->department_id;
        } elseif ($employee->department_id) {
            $assignmentData['department_id'] = $employee->department_id;
        }

        $assignment = ShiftAssignment::create($assignmentData);
        $assignment->load(['employee.user', 'assignedBy']);

        return response()->json([
            'message' => 'Shift assigned successfully',
            'assignment' => $assignment
        ], 201);
    }

    private function createDateRangeAssignments(Request $request, $user, $employee, $startTime, $endTime)
    {
        $startDate = new Carbon($request->start_date);
        $endDate = new Carbon($request->end_date);
        $created = [];
        $skipped = [];

        $daysDiff = $startDate->diffInDays($endDate) + 1;
        if ($daysDiff > 30) {
            return response()->json([
                'message' => 'Date range cannot exceed 30 days'
            ], 400);
        }

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            
            $existing = ShiftAssignment::where('employee_id', $request->employee_id)
                ->whereDate('shift_date', $dateString)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();

            if ($existing) {
                $skipped[] = [
                    'date' => $dateString,
                    'reason' => 'Employee already has a shift assigned'
                ];
            } else {
                $assignmentData = [
                    'employee_id' => $request->employee_id,
                    'assigned_by' => $user->id,
                    'shift_date' => $dateString,
                    'shift_type' => $request->shift_type,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'notes' => $request->notes,
                    'status' => 'pending'
                ];

                if ($employee->business_id) {
                    $assignmentData['business_id'] = $employee->business_id;
                }
                if ($employee->country_id) {
                    $assignmentData['country_id'] = $employee->country_id;
                }
                if ($request->department_id) {
                    $assignmentData['department_id'] = $request->department_id;
                } elseif ($employee->department_id) {
                    $assignmentData['department_id'] = $employee->department_id;
                }

                $assignment = ShiftAssignment::create($assignmentData);
                $assignment->load(['employee.user', 'assignedBy']);
                $created[] = $assignment;
            }

            $currentDate->addDay();
        }

        return response()->json([
            'message' => 'Bulk assignments created successfully',
            'created' => count($created),
            'skipped' => count($skipped),
            'assignments' => $created,
            'summary' => [
                'total_days' => $daysDiff,
                'successful' => count($created),
                'skipped' => $skipped
            ]
        ], 201);
    }
    /**
     * Bulk assign shifts
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_date' => 'required|date',
            'shift_type' => 'required|in:day,night,evening,morning',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            $created = [];
            $skipped = [];

            foreach ($request->employee_ids as $employeeId) {
                $existing = ShiftAssignment::where('employee_id', $employeeId)
                    ->whereDate('shift_date', $request->shift_date)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->exists();

                if ($existing) {
                    $skipped[] = $employeeId;
                    continue;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    $skipped[] = $employeeId;
                    continue;
                }
                
                $assignmentData = [
                    'employee_id' => $employeeId,
                    'assigned_by' => $user->id,
                    'shift_date' => $request->shift_date,
                    'shift_type' => $request->shift_type,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'notes' => $request->notes,
                    'status' => 'pending'
                ];

                if ($employee->business_id) {
                    $assignmentData['business_id'] = $employee->business_id;
                }
                if ($employee->country_id) {
                    $assignmentData['country_id'] = $employee->country_id;
                }
                if ($request->department_id ?? $employee->department_id) {
                    $assignmentData['department_id'] = $request->department_id ?? $employee->department_id;
                }

                $assignment = ShiftAssignment::create($assignmentData);
                // REMOVE employee.department from eager loading
                $assignment->load(['employee.user', 'assignedBy']);
                $created[] = $assignment;
            }

            return response()->json([
                'message' => 'Bulk assignment completed',
                'created' => count($created),
                'skipped' => count($skipped),
                'assignments' => $created
            ], 201);

        } catch (\Exception $e) {
            Log::error('Bulk shift assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to assign shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shift assignment
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'shift_date' => 'sometimes|date',
            'shift_type' => 'sometimes|in:day,night,evening,morning',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'status' => 'sometimes|in:pending,accepted,rejected,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            if (in_array($assignment->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'message' => 'Cannot update completed or cancelled assignments'
                ], 400);
            }

            $assignment->update($request->only([
                'shift_date', 'shift_type', 'start_time', 'end_time', 'notes', 'status'
            ]));

            // REMOVE employee.department from eager loading
            $assignment->load(['employee.user', 'assignedBy']);

            return response()->json([
                'message' => 'Assignment updated successfully',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Assignment update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Employee accepts shift assignment
     */
    public function accept($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            $user = auth()->user();

            if ($user->employee && $user->employee->id !== $assignment->employee_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json([
                    'message' => 'Assignment cannot be accepted'
                ], 400);
            }

            $assignment->accept();
            $assignment->load(['assignedBy']);

            return response()->json([
                'message' => 'Shift accepted successfully',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Accept assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to accept assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Employee rejects shift assignment
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please provide a reason for rejection',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignment = ShiftAssignment::findOrFail($id);
            $user = auth()->user();

            if ($user->employee && $user->employee->id !== $assignment->employee_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json([
                    'message' => 'Assignment cannot be rejected'
                ], 400);
            }

            $assignment->reject($request->reason);
            $assignment->load(['assignedBy']);

            return response()->json([
                'message' => 'Shift rejected',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Reject assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to reject assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel assignment (Manager/Admin only)
     */
    public function cancel($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            if ($assignment->status === 'completed') {
                return response()->json([
                    'message' => 'Cannot cancel completed assignment'
                ], 400);
            }

            $assignment->cancel();
            // REMOVE employee.department from eager loading
            $assignment->load(['employee.user', 'assignedBy']);

            return response()->json([
                'message' => 'Assignment cancelled successfully',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to cancel assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete assignment
     */
    public function destroy($id)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($id);
            
            if ($assignment->status === 'completed') {
                return response()->json([
                    'message' => 'Cannot delete completed assignment'
                ], 400);
            }

            $assignment->delete();

            return response()->json([
                'message' => 'Assignment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete assignment failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's upcoming shifts
     */
    public function myShifts()
    {
        $user = auth()->user();
        
        if (!$user->employee) {
            return response()->json(['assignments' => []]);
        }

        $assignments = ShiftAssignment::where('employee_id', $user->employee->id)
            ->where('shift_date', '>=', Carbon::today())
            ->whereIn('status', ['pending', 'accepted'])
            ->with(['assignedBy', 'department'])
            ->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json(['assignments' => $assignments]);
    }

    /**
     * Get today's shift for employee
     */
    public function todayShift()
    {
        $user = auth()->user();
        
        if (!$user->employee) {
            return response()->json(['assignment' => null]);
        }

        $assignment = ShiftAssignment::where('employee_id', $user->employee->id)
            ->whereDate('shift_date', Carbon::today())
            ->whereIn('status', ['accepted', 'pending'])
            ->with(['assignedBy', 'department'])
            ->first();

        return response()->json(['assignment' => $assignment]);
    }
    
    /**
     * Get shifts assigned by the current user
     */
    public function assignedByMe(Request $request)
    {
        try {
            $user = auth()->user();
            
            Log::info('=== Assigned By Me Called ===', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);

            // Start query with eager loading - REMOVE employee.department
            $query = ShiftAssignment::with([
                'employee.user',  // Keep this to get user info
                'assignedBy',
                'department' // This is ShiftAssignment's department relationship
            ])->where('assigned_by', $user->id);

            // Apply filters if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('shift_date', '>=', $request->from_date);
            }
            
            if ($request->filled('to_date')) {
                $query->whereDate('shift_date', '<=', $request->to_date);
            }

            if ($request->filled('shift_type')) {
                $query->where('shift_type', $request->shift_type);
            }

            // Default: show last 60 days and future shifts
            if (!$request->filled('from_date') && !$request->filled('to_date')) {
                $query->whereDate('shift_date', '>=', Carbon::today()->subDays(60));
            }

            // Order by date (most recent first)
            $assignments = $query->orderBy('shift_date', 'desc')
                                ->orderBy('start_time', 'asc')
                                ->get();

            Log::info('=== Assigned By Me Results ===', [
                'total_assignments' => $assignments->count(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'assignments' => $assignments,
                'total' => $assignments->count(),
                'message' => 'Shifts assigned by you retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('=== Assigned By Me Error ===', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 'Failed to retrieve your assigned shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Country; // Add this import
use App\Models\Business; // Add this import
use App\Models\User; // Add this import
use App\Services\AttendanceService;
use Carbon\Carbon;
use App\Models\ShiftAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller

{
    use AuthorizesRequests;

    public function __construct(private AttendanceService $attendanceService)
    {
    }


    /**
     * Get business-scoped employees query (FIXED - matches EmployeeController logic EXACTLY)
     * 
     * CRITICAL CHANGE: Managers now ONLY see employees with manager_id = their user_id
     * This prevents managers from seeing all employees in their business/department
     */
    private function getBusinessScopedEmployees(Request $request)
    {
        $user = $request->user();
        $requestedBusinessId = $request->query('business_id');
        $query = Employee::query();
        
        Log::info('ATTENDANCE_CONTROLLER: Getting business scoped employees', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'requested_business_id' => $requestedBusinessId,
        ]);
        
        // ADMIN LOGIC (unchanged)
        if ($user->role === 'admin') {
            if ($requestedBusinessId) {
                $businessId = (int)$requestedBusinessId;
                
                if ($user->current_business_id && $user->current_business_id !== $businessId) {
                    if (!$user->businesses()->where('businesses.id', $businessId)->exists()) {
                        Log::warning('ATTENDANCE_CONTROLLER: Admin attempting to access unauthorized business', [
                            'admin_id' => $user->id,
                            'admin_business_id' => $user->current_business_id,
                            'requested_business_id' => $businessId,
                        ]);
                        $query->where('employees.business_id', 0);
                        return $query;
                    }
                }
                
                $query->where('employees.business_id', $businessId);
                
                Log::info('ATTENDANCE_CONTROLLER: Admin filtering by requested business', [
                    'admin_id' => $user->id,
                    'business_id' => $businessId,
                ]);
            }
            elseif ($user->current_business_id) {
                $query->where('employees.business_id', $user->current_business_id);
                
                Log::info('ATTENDANCE_CONTROLLER: Admin filtering by current business', [
                    'admin_id' => $user->id,
                    'business_id' => $user->current_business_id,
                ]);
            }
            elseif ($user->businesses()->exists()) {
                $businessIds = $user->businesses()->pluck('businesses.id');
                $query->whereIn('employees.business_id', $businessIds);
                
                Log::info('ATTENDANCE_CONTROLLER: Admin filtering by managed businesses', [
                    'admin_id' => $user->id,
                    'business_ids' => $businessIds,
                ]);
            }
            else {
                Log::info('ATTENDANCE_CONTROLLER: Super admin - showing all employees', [
                    'admin_id' => $user->id,
                ]);
            }
        }
        // MANAGER LOGIC - CRITICAL FIX: Match EmployeeController exactly
        elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            
            if ($managerEmployee && $managerEmployee->business_id) {
                // FIXED: Only employees where manager_id = this user's ID
                $query->where('employees.business_id', $managerEmployee->business_id)
                      ->where('employees.manager_id', $user->id);
                
                Log::info('ATTENDANCE_CONTROLLER: Manager filtering by business and direct team', [
                    'manager_id' => $user->id,
                    'business_id' => $managerEmployee->business_id,
                    'filter_method' => 'direct_manager_id_only'
                ]);
            } elseif ($managerEmployee) {
                // FIXED: Only directly assigned employees (no business)
                $query->whereNull('employees.business_id')
                      ->where('employees.manager_id', $user->id);
                
                Log::info('ATTENDANCE_CONTROLLER: Manager without business filtering team', [
                    'manager_id' => $user->id,
                    'filter_method' => 'direct_manager_id_no_business'
                ]);
            } else {
                // No employee record - show nothing
                $query->where('employees.id', 0);
                
                Log::info('ATTENDANCE_CONTROLLER: Manager has no employee record', [
                    'manager_id' => $user->id,
                ]);
            }
        }
        // EMPLOYEE LOGIC (unchanged)
        elseif ($user->role === 'employee') {
            $query->where('employees.user_id', $user->id);
            
            Log::info('ATTENDANCE_CONTROLLER: Employee viewing own record', [
                'employee_user_id' => $user->id,
            ]);
        }
        
        return $query;
    }

    /**
     * Get team attendance status for manager (FIXED)
     * GET /api/manager/attendance?date=2025-12-23
     */
    public function getManagerTeamAttendance(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();
            $managerEmployee = $manager->employee;
            
            if (!$managerEmployee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found.'
                ], 404);
            }
            
            $date = $request->input('date', now()->toDateString());
            $targetDate = Carbon::parse($date)->startOfDay();
            
            Log::info('=== Manager Team Attendance Request ===', [
                'manager_id' => $manager->id,
                'manager_employee_id' => $managerEmployee->id,
                'business_id' => $managerEmployee->business_id,
                'department_id' => $managerEmployee->department_id,
                'date' => $targetDate->toDateString()
            ]);
            
            // Get employees using the scoped method
            $employeesQuery = $this->getBusinessScopedEmployees($request);
            $employeesQuery->with(['user']);
            $employees = $employeesQuery->get();
            
            Log::info('=== Manager Team Employees Found ===', [
                'total_employees' => $employees->count(),
                'employee_ids' => $employees->pluck('id')->toArray(),
                'filter_method' => 'direct_manager_assignment'
            ]);
            
            // Get attendance records for these employees on the target date
            $employeeIds = $employees->pluck('id');
            
            $attendances = Attendance::whereIn('employee_id', $employeeIds)
                ->whereDate('date', $targetDate)
                ->get()
                ->keyBy('employee_id');
            
            Log::info('=== Attendance Records Found ===', [
                'total_records' => $attendances->count(),
                'employee_ids_with_attendance' => $attendances->keys()->toArray()
            ]);
            
            // Build attendance data
            $attendanceData = [];
            $summary = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'onLeave' => 0,
                'total_employees' => $employees->count()
            ];
            
            foreach ($employees as $employee) {
                $attendance = $attendances->get($employee->id);
                
                $status = 'absent';
                $clockIn = null;
                $clockOut = null;
                $totalHours = 0;
                
                if ($attendance) {
                    $status = $attendance->status ?? 'present';
                    $clockIn = $attendance->clock_in;
                    $clockOut = $attendance->clock_out;
                    $totalHours = $attendance->total_hours ?? 0;
                }
                
                // Count for summary
                switch ($status) {
                    case 'present':
                    case 'completed':
                        $summary['present']++;
                        break;
                    case 'late':
                        $summary['late']++;
                        break;
                    case 'on_leave':
                        $summary['onLeave']++;
                        break;
                    case 'absent':
                    default:
                        $summary['absent']++;
                        break;
                }
                
                $attendanceData[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name,
                    'employee_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                    'department' => $employee->department ?? 'Unassigned',
                    'position' => $employee->position ?? 'N/A',
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_hours' => round($totalHours, 2),
                    'date' => $targetDate->toDateString(),
                ];
            }
            
            Log::info('=== Manager Team Attendance Summary ===', [
                'summary' => $summary,
                'attendance_data_count' => count($attendanceData)
            ]);
            
            return response()->json([
                'success' => true,
                'attendances' => $attendanceData,
                'summary' => $summary,
                'date' => $targetDate->toDateString()
            ]);
        } catch (\Exception $e) {
            Log::error('=== Manager Team Attendance Failed ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team attendance status for manager (FIXED - Direct assignment only)
     * GET /api/manager/team-status?date=2025-01-08
     */
    public function getTeamStatus(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $user = $request->user();
            $managerEmployee = $user->employee;

            Log::info('=== TEAM STATUS REQUEST START ===', [
                'timestamp' => now()->toDateTimeString(),
                'manager_user_id' => $user->id,
                'manager_email' => $user->email,
                'manager_role' => $user->role,
            ]);

            // Validate manager has employee profile
            if (!$managerEmployee) {
                Log::error('Manager employee profile not found', [
                    'user_id' => $user->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found. Please contact HR.',
                    'attendances' => [],
                    'summary' => $this->getEmptySummary()
                ], 404);
            }

            // Verify user has manager role
            if ($user->role !== 'manager') {
                Log::warning('Non-manager attempting to access team status', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. This endpoint is for managers only.',
                    'attendances' => [],
                    'summary' => $this->getEmptySummary()
                ], 403);
            }

            // Parse and validate date
            $date = $request->input('date', now()->toDateString());
            
            try {
                $targetDate = Carbon::parse($date)->startOfDay();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Use YYYY-MM-DD format.'
                ], 422);
            }

            Log::info('=== MANAGER DETAILS ===', [
                'manager_employee_id' => $managerEmployee->id,
                'manager_user_id' => $user->id,
                'business_id' => $managerEmployee->business_id,
                'department_id' => $managerEmployee->department_id,
                'target_date' => $targetDate->toDateString(),
            ]);

            // CRITICAL FIX: Get only directly managed employees
            $teamEmployees = $this->getManagerTeamEmployees($user, $managerEmployee);
            $totalEmployees = $teamEmployees->count();
            $teamEmployeeIds = $teamEmployees->pluck('id')->toArray();

            Log::info('=== TEAM EMPLOYEES IDENTIFIED ===', [
                'total_team_members' => $totalEmployees,
                'employee_ids' => $teamEmployeeIds,
                'manager_id' => $user->id,
                'business_id' => $managerEmployee->business_id,
                'filter_method' => 'direct_assignment_only',
            ]);

            // If no team members, return empty result
            if ($totalEmployees === 0) {
                Log::warning('Manager has no team members', [
                    'manager_id' => $user->id,
                    'business_id' => $managerEmployee->business_id,
                    'department_id' => $managerEmployee->department_id
                ]);
                
                return response()->json([
                    'success' => true,
                    'attendances' => [],
                    'summary' => $this->getEmptySummary(),
                    'date' => $targetDate->toDateString(),
                    'message' => 'No team members assigned to you. Please contact HR if this is incorrect.'
                ]);
            }

            // Fetch attendance records for target date
            $attendances = Attendance::with(['employee.user', 'shiftAssignment'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereDate('date', $targetDate)
                ->get()
                ->keyBy('employee_id');

            Log::info('=== ATTENDANCE RECORDS RETRIEVED ===', [
                'total_records' => $attendances->count(),
                'employee_ids_with_attendance' => $attendances->keys()->toArray(),
                'employees_without_attendance' => $totalEmployees - $attendances->count()
            ]);

            // Build attendance data and summary
            $attendanceData = [];
            $summary = [
                'total_employees' => $totalEmployees,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'on_leave' => 0,
                'completed' => 0,
                'with_shifts' => 0,
                'without_shifts' => 0,
            ];

            foreach ($teamEmployees as $employee) {
                // Verify employee has user relationship
                if (!$employee->user) {
                    Log::warning('Employee missing user relationship', [
                        'employee_id' => $employee->id
                    ]);
                    continue;
                }

                $attendance = $attendances->get($employee->id);
                
                // Process attendance data
                $attendanceRecord = $this->processEmployeeAttendance(
                    $employee, 
                    $attendance, 
                    $targetDate, 
                    $summary
                );
                
                $attendanceData[] = $attendanceRecord;
            }

            // Sort by employee name for better readability
            usort($attendanceData, function($a, $b) {
                return strcmp($a['full_name'], $b['full_name']);
            });

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('=== TEAM STATUS SUCCESS ===', [
                'summary' => $summary,
                'records_returned' => count($attendanceData),
                'execution_time_ms' => $executionTime
            ]);

            return response()->json([
                'success' => true,
                'attendances' => $attendanceData,
                'summary' => $summary,
                'date' => $targetDate->toDateString(),
                'manager_info' => [
                    'name' => trim($managerEmployee->user->first_name . ' ' . $managerEmployee->user->last_name),
                    'business_id' => $managerEmployee->business_id,
                    'department' => $managerEmployee->department,
                ],
                'debug' => config('app.debug') ? [
                    'execution_time_ms' => $executionTime,
                    'total_employees' => $totalEmployees,
                    'employees_with_attendance' => $attendances->count(),
                    'employees_without_attendance' => $totalEmployees - $attendances->count(),
                    'filter_method' => 'direct_manager_assignment_only',
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('=== TEAM STATUS FAILED ===', [
                'timestamp' => now()->toDateTimeString(),
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get employees that belong to this manager's team
     * 
     * CRITICAL: Only returns employees where manager_id = this user's ID
     * This matches the EmployeeController logic EXACTLY
     * 
     * @param User $user The manager user
     * @param Employee $managerEmployee The manager's employee record
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getManagerTeamEmployees($user, $managerEmployee)
    {
        $query = Employee::with(['user']);
        
        // EXACT SAME LOGIC as EmployeeController
        if ($managerEmployee->business_id) {
            // Manager has business - ONLY employees with manager_id = this user
            $query->where('employees.business_id', $managerEmployee->business_id)
                  ->where('employees.manager_id', $user->id);
            
            Log::info('Building manager team query (with business)', [
                'manager_user_id' => $user->id,
                'manager_employee_id' => $managerEmployee->id,
                'business_id' => $managerEmployee->business_id,
                'filter' => 'business_id + manager_id (DIRECT ASSIGNMENT ONLY)',
            ]);
        } else {
            // Manager without business - ONLY directly assigned employees
            $query->whereNull('employees.business_id')
                  ->where('employees.manager_id', $user->id);
            
            Log::info('Building manager team query (no business)', [
                'manager_user_id' => $user->id,
                'manager_employee_id' => $managerEmployee->id,
                'filter' => 'no business + manager_id (DIRECT ASSIGNMENT ONLY)',
            ]);
        }
        
        $employees = $query->get();
        
        Log::info('Manager team query executed - DIRECT ASSIGNMENTS ONLY', [
            'manager_user_id' => $user->id,
            'total_employees_found' => $employees->count(),
            'employee_ids' => $employees->pluck('id')->toArray(),
            'all_have_manager_id' => $employees->every(function($emp) use ($user) {
                return $emp->manager_id === $user->id;
            }),
        ]);
        
        return $employees;
    }

    /**
     * Process individual employee attendance record
     */
    private function processEmployeeAttendance($employee, $attendance, $targetDate, &$summary)
    {
        // Default values
        $status = 'absent';
        $clockIn = null;
        $clockOut = null;
        $totalHours = 0;
        $attendanceId = null;
        $shift = null;
        $hasShift = false;
        $expectedTime = '08:30:00';
        $isLate = false;
        $minutesLate = 0;

        if ($attendance) {
            $attendanceId = $attendance->id;
            $status = $attendance->status ?? 'present';
            $clockIn = $attendance->clock_in;
            $clockOut = $attendance->clock_out;
            $totalHours = $attendance->total_hours ?? 0;
            
            $shift = $attendance->shiftAssignment;
            $hasShift = $shift !== null;
            
            if ($hasShift) {
                $summary['with_shifts']++;
                $expectedTime = $shift->start_time;
                
                if ($clockIn) {
                    $shiftStart = Carbon::parse($targetDate->toDateString() . ' ' . $shift->start_time);
                    $actualClockIn = Carbon::parse($targetDate->toDateString() . ' ' . $clockIn);
                    $gracePeriod = (int) config('attendance.grace_period_minutes', 15);
                    $lateThreshold = $shiftStart->copy()->addMinutes($gracePeriod);

                    if ($actualClockIn->greaterThan($lateThreshold)) {
                        $isLate = true;
                        $minutesLate = $actualClockIn->diffInMinutes($shiftStart);
                    }
                }
            } else {
                $summary['without_shifts']++;
            }

            $this->updateSummaryStatus($status, $summary);
        } else {
            $summary['absent']++;
        }

        $shiftInfo = null;
        if ($shift) {
            $shiftInfo = [
                'id' => $shift->id,
                'type' => $shift->shift_type,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'status' => $shift->status ?? 'active'
            ];
        }

        return [
            'id' => $attendanceId,
            'employee_id' => $employee->id,
            'full_name' => trim($employee->user->first_name . ' ' . $employee->user->last_name),
            'employee_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
            'department' => $employee->department ?? 'Unassigned',
            'position' => $employee->position ?? 'N/A',
            'status' => $status,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_hours' => round($totalHours, 2),
            'date' => $targetDate->toDateString(),
            'has_shift' => $hasShift,
            'shift' => $shiftInfo,
            'expected_time' => $expectedTime,
            'is_late' => $isLate,
            'minutes_late' => $minutesLate,
        ];
    }

    /**
     * Update summary counts based on attendance status
     */
    private function updateSummaryStatus($status, &$summary)
    {
        switch ($status) {
            case 'completed':
                $summary['completed']++;
                $summary['present']++;
                break;
            case 'present':
                $summary['present']++;
                break;
            case 'late':
                $summary['late']++;
                $summary['present']++;
                break;
            case 'on_leave':
                $summary['on_leave']++;
                break;
            case 'absent':
            default:
                $summary['absent']++;
                break;
        }
    }

    /**
     * Get empty summary structure
     */
    private function getEmptySummary()
    {
        return [
            'total_employees' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'on_leave' => 0,
            'completed' => 0,
            'with_shifts' => 0,
            'without_shifts' => 0,
        ];
    }

    /**
     * Get manager's team attendance history (FIXED - Direct assignment only)
     * GET /api/manager/attendance/team-history
     */
    public function managerTeamHistory(Request $request): JsonResponse
    {
        try {
            $manager = $request->user()->employee;

            if (!$manager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found.'
                ], 404);
            }

            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $perPage = $request->get('per_page', 50);
            $status = $request->get('status');
            $employeeId = $request->get('employee_id');

            Log::info('Fetching team attendance history', [
                'manager_id' => $manager->id,
                'manager_user_id' => $manager->user_id,
                'business_id' => $manager->business_id,
                'department_id' => $manager->department_id,
                'month' => $month,
                'year' => $year
            ]);

            // CRITICAL FIX: Use EXACT same logic as EmployeeController
            $teamQuery = Employee::query();
            
            if ($manager->business_id) {
                // Manager with business - ONLY direct assignments
                $teamQuery->where('business_id', $manager->business_id)
                          ->where('manager_id', $manager->user_id);
            } else {
                // Manager without business - ONLY direct assignments
                $teamQuery->whereNull('business_id')
                          ->where('manager_id', $manager->user_id);
            }
            
            $teamEmployeeIds = $teamQuery->pluck('id')->toArray();

            Log::info('Team members identified - DIRECT ASSIGNMENTS ONLY', [
                'total_team_members' => count($teamEmployeeIds),
                'team_employee_ids' => $teamEmployeeIds,
                'filter_method' => 'manager_id_only (matches EmployeeController)',
                'manager_user_id' => $manager->user_id,
            ]);

            if (empty($teamEmployeeIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                    'employee_summaries' => [],
                    'team_info' => [
                        'total_members' => 0,
                        'department' => $manager->department,
                        'business_id' => $manager->business_id,
                    ],
                    'message' => 'No team members assigned to you.'
                ]);
            }

            // Build query for team members' attendance
            $query = Attendance::with(['employee.user', 'shiftAssignment'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereYear('date', $year)
                ->whereMonth('date', $month);

            // Optional: Filter by specific employee (must be in team)
            if ($employeeId) {
                if (!in_array($employeeId, $teamEmployeeIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to view this employee\'s attendance.'
                    ], 403);
                }
                $query->where('employee_id', $employeeId);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('date', 'desc')
                  ->orderBy('employee_id', 'asc');

            $attendances = $query->paginate($perPage);

            // Group by employee for summary
            $allRecords = Attendance::with(['employee.user'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
            
            $employeeSummaries = $allRecords->groupBy('employee_id')->map(function($records) use ($manager) {
                $employee = $records->first()->employee;
                return [
                    'employee_id' => $employee->id,
                    'name' => trim($employee->user->first_name . ' ' . $employee->user->last_name),
                    'is_manager' => $employee->id === $manager->id,
                    'total_hours' => round($records->sum('total_hours'), 2),
                    'regular_hours' => round($records->sum('regular_hours'), 2),
                    'overtime_hours' => round($records->sum('overtime_hours'), 2),
                    'present_days' => $records->whereIn('status', ['present', 'completed'])->count(),
                    'absent_days' => $records->where('status', 'absent')->count(),
                    'late_days' => $records->where('status', 'late')->count(),
                ];
            })->values();

            // Sort summaries
            $employeeSummaries = $employeeSummaries->sort(function($a, $b) {
                if ($a['is_manager'] && !$b['is_manager']) return -1;
                if (!$a['is_manager'] && $b['is_manager']) return 1;
                return strcmp($a['name'], $b['name']);
            })->values();

            return response()->json([
                'success' => true,
                'data' => AttendanceResource::collection($attendances->items()),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                    'last_page' => $attendances->lastPage(),
                ],
                'employee_summaries' => $employeeSummaries,
                'team_info' => [
                    'total_members' => count($teamEmployeeIds),
                    'department' => $manager->department,
                    'business_id' => $manager->business_id,
                    'filter_method' => 'direct_manager_id (matches EmployeeController)',
                ],
                'filters' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'status' => $status,
                    'employee_id' => $employeeId
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch team attendance history', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team attendance history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

 /**
     * Clock in
     */
    public function clockIn(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact HR.'
                ], 404);
            }

            $attendance = $this->attendanceService->clockIn($employee);

            return response()->json([
                'success' => true,
                'attendance' => $attendance,
                'message' => 'Clocked in successfully at ' . $attendance->clock_in
            ]);
        } catch (\Exception $e) {
            Log::error('Clock-in failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'hint' => 'If you believe this is an error, try the "Reset Status" button.'
            ], 422);
        }
    }
     /**
 * Manager clock out an employee (FIXED - Check manager assignment)
 * POST /api/manager/attendance/{employee}/clock-out
 */
public function managerClockOut(Request $request, Employee $employee): JsonResponse
{
    try {
        $manager = $request->user()->employee;

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Manager profile not found.'
            ], 404);
        }

        // CRITICAL FIX: Verify manager has authority over this employee
        $canManage = false;
        
        // Check 1: Employee is directly assigned to this manager
        if ($employee->manager_id === $request->user()->id) {
            $canManage = true;
        }
        // Check 2: Same business and department (if applicable)
        elseif ($employee->business_id === $manager->business_id) {
            if ($manager->department_id) {
                // Manager has department - employee must be in same department
                if ($employee->department_id === $manager->department_id) {
                    // Only if employee has no other manager or is assigned to this manager
                    if (!$employee->manager_id || $employee->manager_id === $request->user()->id) {
                        $canManage = true;
                    }
                }
            } else {
                // Manager has no department - can manage unassigned employees
                if (!$employee->manager_id) {
                    $canManage = true;
                }
            }
        }

        if (!$canManage) {
            Log::warning('Manager unauthorized clock-out attempt', [
                'manager_id' => $manager->id,
                'manager_user_id' => $request->user()->id,
                'employee_id' => $employee->id,
                'employee_manager_id' => $employee->manager_id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to clock out this employee. This employee is not assigned to you.'
            ], 403);
        }

        $today = now()->toDateString();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No active clock-in found for this employee today.'
            ], 404);
        }

        // Clock out with current time
        $attendance->clock_out = now()->format('H:i:s');
        $attendance->total_hours = $attendance->calculateTotalHours();
        $attendance->status = 'completed';
        $attendance->notes = ($attendance->notes ? $attendance->notes . ' | ' : '') 
            . "Clocked out by manager ({$manager->user->first_name} {$manager->user->last_name}) at " . now()->toDateTimeString();
        $attendance->save();

        Log::info('Manager clocked out employee', [
            'manager_id' => $manager->id,
            'manager_user_id' => $request->user()->id,
            'employee_id' => $employee->id,
            'clock_out' => $attendance->clock_out,
            'total_hours' => $attendance->total_hours
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$employee->user->first_name} {$employee->user->last_name} clocked out successfully",
            'attendance' => [
                'id' => $attendance->id,
                'employee_id' => $employee->id,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'total_hours' => round($attendance->total_hours, 2),
                'status' => $attendance->status
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Manager clock out failed', [
            'manager_id' => $request->user()->id,
            'employee_id' => $employee->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to clock out employee',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
 * Clock in for overtime
 * POST /api/attendance/clock-in-overtime
 */
public function clockInOvertime(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found. Please contact HR.'
            ], 404);
        }

        $attendance = $this->attendanceService->clockInOvertime($employee);

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
            'message' => 'Clocked in for overtime successfully at ' . $attendance->clock_in
        ]);
    } catch (\Exception $e) {
        Log::error('Overtime clock-in failed', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

/**
 * Get enhanced today's status with shift and overtime info
 * GET /api/attendance/today-status
 * 
 * UPDATE THE EXISTING todayStatus METHOD
 */
public function todayStatus(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'status' => 'absent',
                'message' => 'Employee profile not found'
            ]);
        }

        $statusData = $this->attendanceService->getTodayStatus($employee);

        return response()->json([
            'success' => true,
            'status' => $statusData['status'],
            'regular_attendance' => $statusData['regular_attendance'],
            'overtime_attendance' => $statusData['overtime_attendance'],
            'shift' => $statusData['shift'],
            'can_clock_in' => $statusData['can_clock_in'],
            'can_clock_out' => $statusData['can_clock_out'],
            'can_start_overtime' => $statusData['can_start_overtime'],
            'is_in_overtime_session' => $statusData['is_in_overtime_session'],
            'shift_has_ended' => $statusData['shift_has_ended'],
            'expected_shift_end_time' => $statusData['expected_shift_end_time'] 
                ? $statusData['expected_shift_end_time']->format('H:i:s') 
                : null,
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to get today status', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'status' => 'absent',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get overtime summary for an employee
 * GET /api/attendance/overtime-summary?month=1&year=2025
 */
public function overtimeSummary(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Get all attendance records for the month
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $regularSessions = $attendances->where('is_overtime_session', false);
        $overtimeSessions = $attendances->where('is_overtime_session', true);

        $summary = [
            'total_overtime_hours' => round($attendances->sum('overtime_hours'), 2),
            'overtime_sessions_count' => $overtimeSessions->count(),
            'days_with_overtime' => $overtimeSessions->pluck('date')->unique()->count(),
            'total_regular_hours' => round($attendances->sum('regular_hours'), 2),
            'total_all_hours' => round($attendances->sum('total_hours'), 2),
            'breakdown_by_date' => []
        ];

        // Group by date
        $byDate = $attendances->groupBy('date');
        foreach ($byDate as $date => $dayAttendances) {
            $regularAtt = $dayAttendances->where('is_overtime_session', false)->first();
            $overtimeAtts = $dayAttendances->where('is_overtime_session', true);

            $summary['breakdown_by_date'][] = [
                'date' => $date,
                'regular_hours' => $regularAtt ? round($regularAtt->regular_hours ?? 0, 2) : 0,
                'overtime_from_regular' => $regularAtt ? round($regularAtt->overtime_hours ?? 0, 2) : 0,
                'overtime_sessions' => round($overtimeAtts->sum('total_hours'), 2),
                'total_overtime' => round(
                    ($regularAtt ? $regularAtt->overtime_hours : 0) + $overtimeAtts->sum('total_hours'), 
                    2
                ),
                'total_hours' => round($dayAttendances->sum('total_hours'), 2)
            ];
        }

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get overtime summary', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch overtime summary',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get detailed attendance with overtime breakdown
 * GET /api/attendance/detailed-history?month=1&year=2025
 */
public function detailedHistory(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $attendances = Attendance::where('employee_id', $employee->id)
            ->with(['shiftAssignment', 'parentAttendance'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Group by date for better organization
        $groupedByDate = [];
        foreach ($attendances as $attendance) {
            $date = $attendance->date->format('Y-m-d');
            
            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [
                    'date' => $date,
                    'day_of_week' => $attendance->date->format('l'),
                    'regular_session' => null,
                    'overtime_sessions' => [],
                    'totals' => [
                        'regular_hours' => 0,
                        'overtime_hours' => 0,
                        'total_hours' => 0
                    ]
                ];
            }

            $sessionData = [
                'id' => $attendance->id,
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'total_hours' => round($attendance->total_hours ?? 0, 2),
                'regular_hours' => round($attendance->regular_hours ?? 0, 2),
                'overtime_hours' => round($attendance->overtime_hours ?? 0, 2),
                'status' => $attendance->status,
                'shift' => $attendance->shiftAssignment ? [
                    'type' => $attendance->shiftAssignment->shift_type,
                    'start_time' => $attendance->shiftAssignment->start_time,
                    'end_time' => $attendance->shiftAssignment->end_time
                ] : null
            ];

            if ($attendance->is_overtime_session) {
                $groupedByDate[$date]['overtime_sessions'][] = $sessionData;
            } else {
                $groupedByDate[$date]['regular_session'] = $sessionData;
            }

            // Update totals
            $groupedByDate[$date]['totals']['regular_hours'] += $attendance->regular_hours ?? 0;
            $groupedByDate[$date]['totals']['overtime_hours'] += $attendance->overtime_hours ?? 0;
            $groupedByDate[$date]['totals']['total_hours'] += $attendance->total_hours ?? 0;
        }

        // Round totals
        foreach ($groupedByDate as &$day) {
            $day['totals']['regular_hours'] = round($day['totals']['regular_hours'], 2);
            $day['totals']['overtime_hours'] = round($day['totals']['overtime_hours'], 2);
            $day['totals']['total_hours'] = round($day['totals']['total_hours'], 2);
        }

        return response()->json([
            'success' => true,
            'data' => array_values($groupedByDate),
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get detailed history', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch detailed history',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Clock out
     */
    public function clockOut(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact HR.'
                ], 404);
            }

            $attendance = $this->attendanceService->clockOut($employee);

            return response()->json([
                'success' => true,
                'attendance' => $attendance,
                'message' => 'Clocked out successfully. Total hours: ' . round($attendance->total_hours, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('Clock-out failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    /**
 * Get attendance history for a specific employee (FIXED - Manager authorization by assignment)
 * GET /api/manager/attendance/{employee}/history
 */
public function employeeHistory(Request $request, Employee $employee): JsonResponse
{
    try {
        // Get the requesting user
        $user = $request->user();
        
        // If user is a manager, verify they can access this employee
        if ($user->role === 'manager') {
            $manager = $user->employee;
            
            if (!$manager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found.'
                ], 404);
            }

            // CRITICAL FIX: Check if employee is assigned to this manager
            $canAccess = false;
            
            // Check 1: Employee has this manager assigned via manager_id
            if ($employee->manager_id === $user->id) {
                $canAccess = true;
                Log::info('Manager accessing assigned employee', [
                    'manager_id' => $manager->id,
                    'employee_id' => $employee->id,
                    'access_reason' => 'direct_assignment'
                ]);
            }
            // Check 2: Same business and department (if manager has department)
            elseif ($employee->business_id === $manager->business_id) {
                if ($manager->department_id) {
                    // Manager has department - employee must be in same department
                    if ($employee->department_id === $manager->department_id) {
                        // Only if employee has no other manager assigned
                        if (!$employee->manager_id || $employee->manager_id === $user->id) {
                            $canAccess = true;
                            Log::info('Manager accessing department employee', [
                                'manager_id' => $manager->id,
                                'employee_id' => $employee->id,
                                'access_reason' => 'same_department',
                                'department_id' => $manager->department_id
                            ]);
                        }
                    }
                } else {
                    // Manager has no department - can access unassigned employees in same business
                    if (!$employee->manager_id) {
                        $canAccess = true;
                        Log::info('Manager accessing unassigned employee', [
                            'manager_id' => $manager->id,
                            'employee_id' => $employee->id,
                            'access_reason' => 'unassigned_in_business'
                        ]);
                    }
                }
            }
            // Check 3: Manager viewing their own record
            elseif ($employee->id === $manager->id) {
                $canAccess = true;
                Log::info('Manager accessing own record', [
                    'manager_id' => $manager->id,
                    'employee_id' => $employee->id,
                    'access_reason' => 'self'
                ]);
            }

            if (!$canAccess) {
                Log::warning('Manager unauthorized access attempt', [
                    'manager_id' => $manager->id,
                    'manager_user_id' => $user->id,
                    'employee_id' => $employee->id,
                    'employee_manager_id' => $employee->manager_id,
                    'employee_business_id' => $employee->business_id,
                    'manager_business_id' => $manager->business_id,
                    'employee_department' => $employee->department_id,
                    'manager_department' => $manager->department_id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this employee\'s attendance. This employee is not assigned to you.'
                ], 403);
            }
        }

        // Proceed with fetching history
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $query = Attendance::with('shiftAssignment')
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc');

        $attendances = $query->get();

        // Calculate summary with overtime
        $summary = [
            'total_records' => $attendances->count(),
            'total_hours' => round($attendances->sum('total_hours'), 2),
            'regular_hours' => round($attendances->sum('regular_hours'), 2),
            'overtime_hours' => round($attendances->sum('overtime_hours'), 2),
            'present_days' => $attendances->whereIn('status', ['present', 'completed'])->count(),
            'absent_days' => 0, // Calculate based on working days
            'late_days' => $attendances->where('status', 'late')->count(),
            'overtime_sessions' => $attendances->where('is_overtime_session', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $attendances,
            'summary' => $summary,
            'employee' => [
                'id' => $employee->id,
                'name' => trim($employee->user->first_name . ' ' . $employee->user->last_name),
                'employee_number' => $employee->employee_id,
                'department' => $employee->department,
                'position' => $employee->position,
                'manager_id' => $employee->manager_id,
            ],
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch employee history', [
            'employee_id' => $employee->id ?? null,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch history',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Force reset attendance status (closes all open records)
     */
    public function forceReset(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact HR.'
                ], 404);
            }

            $closedCount = $this->attendanceService->forceCloseAllOpen($employee);

            return response()->json([
                'success' => true,
                'message' => "Successfully reset attendance status. $closedCount record(s) were auto-closed.",
                'closed_count' => $closedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Force reset failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset status: ' . $e->getMessage()
            ], 500);
        }
    }


/**
 * Get attendance history for a specific date (alternative endpoint)
 */
public function historyByDate(Request $request, string $date): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found. Please contact HR.'
            ], 404);
        }

        // Validate and parse the date
        try {
            $targetDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Please use YYYY-MM-DD format.'
            ], 422);
        }

        // Get attendance record for the specific date
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $targetDate)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No attendance record found for this date.',
                'date' => $targetDate
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AttendanceResource($attendance),
            'date' => $targetDate
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch attendance by date', [
            'user_id' => $request->user()->id,
            'date' => $date,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch attendance record',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function adminHistory(Request $request): JsonResponse
{
    try {
        // Authorization check (assuming you have a policy)
        // $this->authorize('viewAny', Attendance::class);
        $employeeId = $request->get('employee_id');
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $perPage = $request->get('per_page', 50);
        $status = $request->get('status');
        $department = $request->get('department');
        $scopedEmployees = $this->getBusinessScopedEmployees($request)->pluck('id');
        $query = Attendance::with(['employee'])
            ->whereIn('employee_id', $scopedEmployees)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);
        // Filter by specific employee if provided
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        // Filter by department if provided
        if ($department) {
            $query->whereHas('employee', function($q) use ($department) {
                $q->where('department', $department);
            });
        }
        // Filter by status if provided
        if ($status) {
            $query->where('status', $status);
        }
        $query->orderBy('date', 'desc')
              ->orderBy('employee_id', 'asc');
        $attendances = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances->items()),
            'pagination' => [
                'current_page' => $attendances->currentPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
                'last_page' => $attendances->lastPage(),
            ],
            'filters' => [
                'employee_id' => $employeeId,
                'month' => (int) $month,
                'year' => (int) $year,
                'status' => $status,
                'department' => $department
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Admin history fetch failed', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch attendance history',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Store attendance record
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact HR.'
                ], 404);
            }

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => $request->date,
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'break_minutes' => $request->break_minutes ?? 0,
                'notes' => $request->notes,
            ]);

            // Calculate total hours
            $attendance->total_hours = $attendance->calculateTotalHours();
            $attendance->save();

            return response()->json([
                'success' => true,
                'attendance' => new AttendanceResource($attendance),
                'message' => 'Attendance recorded successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store attendance', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update attendance record
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        try {
            $this->authorize('update', $attendance);

            $attendance->update([
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'break_minutes' => $request->break_minutes ?? 0,
                'notes' => $request->notes,
            ]);

            // Recalculate total hours
            $attendance->total_hours = $attendance->calculateTotalHours();
            $attendance->save();

            return response()->json([
                'success' => true,
                'attendance' => new AttendanceResource($attendance),
                'message' => 'Attendance updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update attendance', [
                'user_id' => $request->user()->id,
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly summary/stats
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee profile not found. Please contact HR.'
                ], 404);
            }

            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));

            $summary = $this->attendanceService->getMonthlySummary($employee->id, $month, $year);

            return response()->json([
                'stats' => [
                    'totalHours' => $summary['total_hours'],
                    'attendanceRate' => $summary['attendance_rate'],
                    'lateDays' => $summary['late_days'],
                    'workdays' => $summary['working_days'],
                    'presentDays' => $summary['present_days'],
                    'absentDays' => $summary['absent_days']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance summary', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
  /**
 * Get attendance status for all employees on a specific date with filters
 */
public function getAttendanceStatus(Request $request): JsonResponse
{
    try {
        $date = $request->input('date', now()->toDateString());
        $countryId = $request->input('country_id');
        $businessId = $request->input('business_id');
        $department = $request->input('department');
        
        $targetDate = Carbon::parse($date)->startOfDay();
        Log::info('Fetching attendance status', [
            'date' => $targetDate->toDateString(),
            'country_id' => $countryId,
            'business_id' => $businessId,
            'department' => $department,
            'user_id' => $request->user()->id
        ]);
        
        // Build employees query with filters
        $employeesQuery = $this->getBusinessScopedEmployees($request);
        
        // Add the join and select with proper table prefixes
        $employeesQuery->select(
                'employees.id',
                'employees.employee_id',
                'employees.department',
                'employees.position',
                'employees.country_id',
                'employees.business_id as employee_business_id',
                'users.first_name',
                'users.last_name',
                'users.email'
            )
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->orderBy('users.first_name');
        
        // Apply filters - use explicit table prefixes
        if ($countryId) {
            $employeesQuery->where('employees.country_id', $countryId);
        }
        
        if ($businessId) {
            $employeesQuery->where('employees.business_id', $businessId);
        }
        
        if ($department) {
            $employeesQuery->where('employees.department', $department);
        }
        
        // Get the SQL query for debugging
        $sql = $employeesQuery->toSql();
        Log::info('Attendance status query', ['sql' => $sql, 'bindings' => $employeesQuery->getBindings()]);
        
        $employees = $employeesQuery->get();
        
        // Rest of your existing code remains the same...
        // Get attendance records for the specified date with filters
        $attendancesQuery = Attendance::where('date', $targetDate->toDateString());
        
        if ($countryId) {
            $attendancesQuery->where('country_id', $countryId);
        }
        
        if ($businessId) {
            $attendancesQuery->where('business_id', $businessId);
        }
        
        if ($department) {
            $attendancesQuery->whereHas('employee', function($q) use ($department) {
                $q->where('department', $department);
            });
        }
        
        $attendances = $attendancesQuery->get()->keyBy('employee_id');
        
        // Get country and business names for display
        $employeeCountryIds = $employees->pluck('country_id')->filter()->unique()->values();
        $employeeBusinessIds = $employees->pluck('employee_business_id')->filter()->unique()->values();
        
        $countries = Country::whereIn('id', $employeeCountryIds)->get()->keyBy('id');
        $businesses = Business::whereIn('id', $employeeBusinessIds)->get()->keyBy('id');
        
        // Build response data
        $data = [];
        $presentCount = 0;
        $absentCount = 0;
        $lateCount = 0;
        
        foreach ($employees as $employee) {
            $attendance = $attendances->get($employee->id);
            
            $status = 'absent';
            $clockIn = null;
            $clockOut = null;
            $totalHours = 0;
            
            if ($attendance) {
                $status = $attendance->status ?? 'present';
                $clockIn = $attendance->clock_in;
                $clockOut = $attendance->clock_out;
                $totalHours = $attendance->total_hours ?? 0;
                
                if (in_array($status, ['present', 'completed'])) {
                    $presentCount++;
                } elseif ($status === 'late') {
                    $lateCount++;
                } else {
                    $absentCount++;
                }
            } else {
                $absentCount++;
            }
            
            $country = $countries->get($employee->country_id);
            $business = $businesses->get($employee->employee_business_id);
            
            $data[] = [
                'employee_id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'employee_id_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                'department' => $employee->department ?? 'Unassigned',
                'position' => $employee->position ?? 'N/A',
                'country_id' => $employee->country_id,
                'business_id' => $employee->employee_business_id,
                'country_name' => $country ? $country->name : null,
                'business_name' => $business ? $business->name : null,
                'status' => $status,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_hours' => round($totalHours, 2),
                'date' => $targetDate->toDateString(),
            ];
        }
        
        $totalEmployees = $employees->count();
        $attendanceRate = $totalEmployees > 0
            ? round((($presentCount + $lateCount) / $totalEmployees) * 100, 2)
            : 0;
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total_employees' => $totalEmployees,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'attendance_rate' => $attendanceRate
            ],
            'filters' => [
                'date' => $targetDate->toDateString(),
                'country_id' => $countryId,
                'business_id' => $businessId,
                'department' => $department
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to fetch attendance status', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch attendance status',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Get attendance history with enhanced filtering
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.'
                ], 404);
            }

            $month = $request->get('month', date('m'));
            $year = $request->get('year', date('Y'));
            $perPage = $request->get('per_page', 31);
            $status = $request->get('status');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $countryId = $request->get('country_id');
            $businessId = $request->get('business_id');

            $query = Attendance::where('employee_id', $employee->id);

            // Apply date filters
            if ($startDate && $endDate) {
                $query->whereBetween('date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                $query->whereYear('date', $year)
                      ->whereMonth('date', $month);
            }

            // Apply location filters
            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('date', 'desc')
                  ->orderBy('clock_in', 'desc');

            $attendances = $query->paginate($perPage);

            // Calculate summary
            $allRecords = $query->get();
            $summary = [
                'total_records' => $allRecords->count(),
                'total_hours' => round($allRecords->sum('total_hours'), 2),
                'present_days' => $allRecords->whereIn('status', ['present', 'completed'])->count(),
                'absent_days' => $allRecords->where('status', 'absent')->count(),
                'late_days' => $allRecords->where('status', 'late')->count(),
                'average_hours_per_day' => $allRecords->count() > 0 
                    ? round($allRecords->avg('total_hours'), 2) 
                    : 0
            ];

            return response()->json([
                'success' => true,
                'data' => AttendanceResource::collection($attendances->items()),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                    'last_page' => $attendances->lastPage(),
                ],
                'summary' => $summary,
                'filters' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'status' => $status,
                    'country_id' => $countryId,
                    'business_id' => $businessId,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance history', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark employee present with auto-populated country/business
     */
    public function markPresent(Request $request, Employee $employee): JsonResponse
    {
        try {
            $date = $request->input('date');

            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date is required.'
                ], 422);
            }

            $attendanceDate = Carbon::parse($date)->startOfDay();
            $clockIn = $request->input('clock_in', '08:00:00');
            $clockOut = $request->input('clock_out', '17:00:00');

            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $attendanceDate)
                ->first();

            $now = now();
            $adminNote = "Manually marked present by " . $request->user()->name . " on " . $now->toDateTimeString();

            if ($existingAttendance) {
                $existingAttendance->update([
                    'status' => 'present',
                    'clock_in' => $existingAttendance->clock_in ?? $clockIn,
                    'clock_out' => $existingAttendance->clock_out ?? $clockOut,
                    'notes' => ($existingAttendance->notes ? $existingAttendance->notes . ' | ' : '') . $adminNote,
                ]);

                $existingAttendance->total_hours = $existingAttendance->calculateTotalHours();
                $existingAttendance->save();

                $attendance = $existingAttendance;
            } else {
                // Auto-populate country and business from employee
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'country_id' => $employee->country_id,
                    'business_id' => $employee->business_id,
                    'date' => $attendanceDate,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_minutes' => $request->input('break_minutes', 0),
                    'status' => 'present',
                    'notes' => $adminNote,
                ]);

                $attendance->total_hours = $attendance->calculateTotalHours();
                $attendance->save();
            }

            return response()->json([
                'success' => true,
                'data' => new AttendanceResource($attendance),
                'message' => "Employee marked as present for {$attendanceDate->toDateString()}."
            ]);

        } catch (\Exception $e) {
            Log::error('Mark present failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark employee as present',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   /**
 * Get countries with employee counts (for filter dropdown)
 */
public function getCountries(Request $request): JsonResponse
{
    try {
        $countries = Country::select(
                'countries.id',
                'countries.name',
                'countries.code',
                'countries.currency_code',
                'countries.currency_symbol',
                DB::raw('COUNT(DISTINCT employees.id) as employee_count')
            )
            ->leftJoin('employees', 'countries.id', '=', 'employees.country_id')
            // Remove the is_active filter if column doesn't exist
            // ->where('employees.is_active', true)
            ->groupBy('countries.id', 'countries.name', 'countries.code', 
                     'countries.currency_code', 'countries.currency_symbol')
            ->orderBy('countries.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch countries', [
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch countries: ' . $e->getMessage()
        ], 500);
    }
}

    /**
 * Get businesses with employee counts (for filter dropdown)
 */
public function getBusinesses(Request $request): JsonResponse
{
    try {
        $user = $request->user();
        $countryId = $request->get('country_id');
        $search = $request->get('search');

        Log::info('ATTENDANCE_CONTROLLER: getBusinesses called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'business_id' => $user->current_business_id,
            'country_id' => $countryId
        ]);

        $query = Business::select(
                'businesses.id',
                'businesses.name',
                'businesses.legal_name',
                'businesses.country_id',
                'countries.name as country_name',
                DB::raw('COUNT(DISTINCT employees.id) as employee_count')
            )
            ->leftJoin('employees', function($join) use ($user) {
                $join->on('businesses.id', '=', 'employees.business_id');
                
                // If admin has business, scope to that business
                if ($user->role === 'admin' && $user->current_business_id) {
                    $join->where('employees.business_id', $user->current_business_id);
                }
            })
            ->leftJoin('countries', 'businesses.country_id', '=', 'countries.id')
            ->where('businesses.status', 'active');

        // Filter by country if provided
        if ($countryId) {
            $query->where('businesses.country_id', $countryId);
        }

        // If admin doesn't have a business, show only businesses in their country
        if ($user->role === 'admin' && !$user->current_business_id && $user->current_country_code) {
            $query->where('countries.code', $user->current_country_code);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('businesses.name', 'like', "%{$search}%")
                  ->orWhere('businesses.legal_name', 'like', "%{$search}%");
            });
        }

        $businesses = $query->groupBy(
                'businesses.id', 
                'businesses.name', 
                'businesses.legal_name', 
                'businesses.country_id',
                'countries.name'
            )
            ->orderBy('businesses.name')
            ->get();

        Log::info('ATTENDANCE_CONTROLLER: Businesses retrieved', [
            'count' => $businesses->count(),
            'business_scoped' => $user->role === 'admin' && $user->current_business_id ? 'yes' : 'no'
        ]);

        return response()->json([
            'success' => true,
            'data' => $businesses
        ]);

    } catch (\Exception $e) {
        Log::error('ATTENDANCE_CONTROLLER: Failed to fetch businesses', [
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch businesses: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Bulk mark multiple employees as present
     */
    public function bulkMarkPresent(Request $request): JsonResponse
    {
        try {
           // $this->authorize('viewAny', Attendance::class);

            $validated = $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'date' => 'required|date',
                'clock_in' => 'nullable|date_format:H:i:s',
                'clock_out' => 'nullable|date_format:H:i:s',
            ]);

            $date = Carbon::parse($validated['date'])->startOfDay();
            $clockIn = $validated['clock_in'] ?? '08:00:00';
            $clockOut = $validated['clock_out'] ?? '17:00:00';

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            DB::beginTransaction();
            try {
                foreach ($validated['employee_ids'] as $employeeId) {
                    try {
                        $employee = Employee::findOrFail($employeeId);
                        
                        $attendance = Attendance::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'date' => $date
                            ],
                            [
                                'clock_in' => $clockIn,
                                'clock_out' => $clockOut,
                                'status' => 'present',
                                'notes' => 'Bulk marked present by admin on ' . now()->toDateTimeString(),
                            ]
                        );

                        $attendance->total_hours = $attendance->calculateTotalHours();
                        $attendance->save();

                        $results[] = [
                            'employee_id' => $employee->id,
                            'name' => "{$employee->first_name} {$employee->last_name}",
                            'success' => true
                        ];
                        $successCount++;

                    } catch (\Exception $e) {
                        $results[] = [
                            'employee_id' => $employeeId,
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                        $failureCount++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Bulk mark present completed: {$successCount} succeeded, {$failureCount} failed",
                    'results' => $results,
                    'summary' => [
                        'total' => count($validated['employee_ids']),
                        'success' => $successCount,
                        'failed' => $failureCount
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Bulk mark present failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current attendance statuses (who is clocked in right now)
     */
    public function currentStatuses(Request $request): JsonResponse
    {
        try {
           // $this->authorize('viewAny', Attendance::class);

            $today = now()->toDateString();
            
            $employees = Employee::with(['user'])
                ->leftJoin('attendances', function($join) use ($today) {
                    $join->on('employees.id', '=', 'attendances.employee_id')
                         ->whereDate('attendances.date', $today);
                })
                ->select('employees.*', 'attendances.status', 'attendances.clock_in', 'attendances.clock_out')
                ->get();

            $data = $employees->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'department' => $employee->department,
                    'status' => $employee->status ?? 'absent',
                    'clock_in' => $employee->clock_in,
                    'clock_out' => $employee->clock_out,
                ];
            });

            $presentCount = $data->whereIn('status', ['present', 'completed'])->count();
            $absentCount = $data->where('status', 'absent')->count();

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => [
                    'total' => $data->count(),
                    'present' => $presentCount,
                    'absent' => $absentCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch current statuses', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch current statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
 * Get monthly total hours and statistics
 * GET /api/attendance/monthly-stats?month=11&year=2025
 */
public function monthlyStats(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Validate inputs
        if ($month < 1 || $month > 12) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid month. Must be between 1 and 12.'
            ], 422);
        }

        Log::info('Fetching monthly stats', [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year
        ]);

        // Get comprehensive monthly statistics
        $stats = Attendance::getMonthlyStats($employee->id, $month, $year);

        // Get monthly overtime
        $overtimeHours = Attendance::getMonthlyOvertimeHours($employee->id, $month, $year);

        // Get working days in month (excluding weekends)
        $workingDays = $this->getWorkingDaysInMonth($month, $year);

        // Calculate attendance rate
        $attendanceRate = $workingDays > 0 
            ? round(($stats['completed_days'] / $workingDays) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_hours' => $stats['total_hours'],
                'total_days_worked' => $stats['completed_days'],
                'average_hours_per_day' => $stats['average_hours_per_day'],
                'overtime_hours' => $overtimeHours,
                'present_days' => $stats['present_days'],
                'late_days' => $stats['late_days'],
                'absent_days' => $workingDays - $stats['total_days'],
                'incomplete_days' => $stats['incomplete_days'],
                'working_days_in_month' => $workingDays,
                'attendance_rate' => $attendanceRate,
            ],
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch monthly stats', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch monthly statistics',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get detailed breakdown by day for a month
 * GET /api/attendance/monthly-breakdown?month=11&year=2025
 */
public function monthlyBreakdown(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Get all attendance records for the month
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();

        // Build daily breakdown
        $breakdown = $attendances->map(function($attendance) {
            $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
            $overtimeHours = max(0, $attendance->total_hours - $overtimeThreshold);

            return [
                'date' => $attendance->date,
                'day_of_week' => date('l', strtotime($attendance->date)),
                'clock_in' => $attendance->clock_in,
                'clock_out' => $attendance->clock_out,
                'total_hours' => round($attendance->total_hours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'break_minutes' => $attendance->break_minutes,
                'status' => $attendance->status,
                'notes' => $attendance->notes,
            ];
        });

        // Calculate totals
        $totalHours = $attendances->sum('total_hours');
        $totalOvertime = $attendances->sum(function($a) {
            return max(0, $a->total_hours - config('payroll.attendance.overtime_threshold', 8));
        });

        return response()->json([
            'success' => true,
            'breakdown' => $breakdown,
            'summary' => [
                'total_hours' => round($totalHours, 2),
                'total_overtime' => round($totalOvertime, 2),
                'total_days' => $breakdown->count(),
                'average_daily_hours' => $breakdown->count() > 0 
                    ? round($totalHours / $breakdown->count(), 2) 
                    : 0
            ],
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1))
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch monthly breakdown', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch monthly breakdown',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Recalculate hours for employee's records
 * POST /api/attendance/recalculate-hours
 */
public function recalculateHours(Request $request): JsonResponse
{
    try {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.'
            ], 404);
        }

        $month = $request->get('month');
        $year = $request->get('year');

        Log::info('Recalculating hours', [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year
        ]);

        // Get records to update
        $query = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('clock_out');

        if ($month && $year) {
            $query->whereMonth('date', $month)
                  ->whereYear('date', $year);
        }

        $records = $query->get();
        $updated = 0;

        foreach ($records as $record) {
            $oldHours = $record->total_hours;
            $newHours = $record->calculateTotalHours();
            
            // Only update if different
            if (abs($oldHours - $newHours) > 0.01) {
                $record->total_hours = $newHours;
                $record->save();
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Recalculated hours for {$updated} records",
            'details' => [
                'total_records' => $records->count(),
                'updated_records' => $updated,
                'no_change_records' => $records->count() - $updated
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to recalculate hours', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to recalculate hours',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Helper: Get working days in month (excluding weekends)
 */
private function getWorkingDaysInMonth(int $month, int $year): int
{
    $days = 0;
    $date = Carbon::create($year, $month, 1);
    $daysInMonth = $date->daysInMonth;

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = Carbon::create($year, $month, $day);
        if (!$currentDate->isWeekend()) {
            $days++;
        }
    }

    return $days;
}
}
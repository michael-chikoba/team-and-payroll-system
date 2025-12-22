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
                'attendance' => new AttendanceResource($attendance),
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
                'attendance' => new AttendanceResource($attendance),
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
     * Get today's status
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

            $status = $this->attendanceService->getTodayStatus($employee);

            // Get today's attendance details
            $today = now()->timezone('Africa/Lusaka')->toDateString();
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            return response()->json([
                'status' => $status,
                'attendance' => $attendance ? new AttendanceResource($attendance) : null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get today status', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'absent',
                'error' => $e->getMessage()
            ]);
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

/**
 * Get attendance history for admin (all employees or specific employee)
 */
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

        $query = Attendance::with(['employee'])
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
 * Get attendance history for a specific employee (Manager access)
 */
public function employeeHistory(Request $request, Employee $employee): JsonResponse
{
    try {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $perPage = $request->get('per_page', 31);
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        Log::info('Manager fetching employee attendance history', [
            'manager_user_id' => $request->user()->id,
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year
        ]);

        // Build the query
        $query = Attendance::where('employee_id', $employee->id);

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } else {
            $query->whereYear('date', $year)
                  ->whereMonth('date', $month);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('date', 'desc')
              ->orderBy('clock_in', 'desc');

        // Get paginated results
        $attendances = $query->paginate($perPage);

        // Get employee with user relationship
        $employee->load('user');
        
        // Get full name from user relationship or use the employee's full_name attribute
        $fullName = $employee->full_name;

        // Calculate summary
        $allRecords = $query->get();
        $summary = [
            'employee_name' => $fullName,
            'employee_id' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
            'department' => $employee->department ?? 'Unassigned',
            'position' => $employee->position ?? 'N/A',
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
                'from' => $attendances->firstItem(),
                'to' => $attendances->lastItem(),
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $summary['employee_name'],
                'employee_id' => $summary['employee_id'],
                'department' => $summary['department'],
                'position' => $summary['position']
            ],
            'summary' => $summary,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to fetch employee attendance history for manager', [
            'manager_user_id' => $request->user()->id,
            'employee_id' => $employee->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch employee attendance history',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get manager's team attendance history (all team members)
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
        $employeeId = $request->get('employee_id'); // Optional filter

        Log::info('Fetching team attendance history', [
            'manager_id' => $manager->id,
            'month' => $month,
            'year' => $year
        ]);

        // Get manager's team members (you'll need to implement team relationship)
        // For now, assuming all employees are accessible
        $query = Attendance::with(['employee'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        // Optional: Filter by specific employee
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        // Optional: Filter by manager's team only
        // Uncomment if you have a manager_id field in employees table
        // $query->whereHas('employee', function($q) use ($manager) {
        //     $q->where('manager_id', $manager->id);
        // });

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('date', 'desc')
              ->orderBy('employee_id', 'asc');

        $attendances = $query->paginate($perPage);

        // Group by employee for summary
        $allRecords = $query->get();
        $employeeSummaries = $allRecords->groupBy('employee_id')->map(function($records) {
            $employee = $records->first()->employee;
            return [
                'employee_id' => $employee->id,
                'name' => "{$employee->first_name} {$employee->last_name}",
                'total_hours' => round($records->sum('total_hours'), 2),
                'present_days' => $records->whereIn('status', ['present', 'completed'])->count(),
                'absent_days' => $records->where('status', 'absent')->count(),
                'late_days' => $records->where('status', 'late')->count(),
            ];
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
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch team attendance history',
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

        // Build employees query with filters - JOIN with users table
        $employeesQuery = Employee::select(
                'employees.id',
                'employees.employee_id',
                'employees.department',
                'employees.position',
                'employees.country_id',
                'employees.business_id',
                'users.first_name',
                'users.last_name',
                'users.email'
            )
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->orderBy('users.first_name');

        // Apply filters
        if ($countryId) {
            $employeesQuery->where('employees.country_id', $countryId);
        }

        if ($businessId) {
            $employeesQuery->where('employees.business_id', $businessId);
        }

        if ($department) {
            $employeesQuery->where('employees.department', $department);
        }

        $employees = $employeesQuery->get();

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
        $countries = Country::whereIn('id', $employees->pluck('country_id')->filter()->unique()->values())->get()->keyBy('id');
        $businesses = Business::whereIn('id', $employees->pluck('business_id')->filter()->unique()->values())->get()->keyBy('id');

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
            $business = $businesses->get($employee->business_id);

            $data[] = [
                'employee_id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'employee_id_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                'department' => $employee->department ?? 'Unassigned',
                'position' => $employee->position ?? 'N/A',
                'country_id' => $employee->country_id,
                'business_id' => $employee->business_id,
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
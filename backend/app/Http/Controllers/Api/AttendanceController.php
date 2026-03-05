<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Country;
use App\Models\Business;
use App\Models\Leave;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ActivityTrackingService;
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

    public function __construct(
        private AttendanceService $attendanceService,
        private ActivityTrackingService $activityTrackingService
    ) {}

    /**
     * Get business-scoped employees query
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
        
        // ADMIN LOGIC
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
            }
            elseif ($user->current_business_id) {
                $query->where('employees.business_id', $user->current_business_id);
            }
            elseif ($user->businesses()->exists()) {
                $businessIds = $user->businesses()->pluck('businesses.id');
                $query->whereIn('employees.business_id', $businessIds);
            }
        }
        // MANAGER LOGIC
        elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            
            if ($managerEmployee && $managerEmployee->business_id) {
                $query->where('employees.business_id', $managerEmployee->business_id)
                      ->where('employees.manager_id', $user->id);
            } elseif ($managerEmployee) {
                $query->whereNull('employees.business_id')
                      ->where('employees.manager_id', $user->id);
            } else {
                $query->where('employees.id', 0);
            }
        }
        // EMPLOYEE LOGIC
        elseif ($user->role === 'employee') {
            $query->where('employees.user_id', $user->id);
        }
        
        return $query;
    }

    // =========================================================================
    // HELPER: Build a full calendar of days with attendance + leave status
    // =========================================================================

    /**
     * Build a complete day-by-day history for a given employee over a date range.
     *
     * Every calendar day in the range is represented:
     *   - Days with an attendance record  → use that record's status (present / late)
     *   - Days covered by an approved leave → status = on_leave
     *   - Remaining weekdays with no record  → status = absent
     *   - Weekends                            → status = weekend  (excluded from summary counts)
     *
     * @param  int         $employeeId
     * @param  Carbon      $from        Start of range (inclusive)
     * @param  Carbon      $to          End of range   (inclusive, capped to today)
     * @return array{records: array, summary: array}
     */
    private function buildFullHistory(int $employeeId, Carbon $from, Carbon $to): array
    {
        $today = Carbon::today();
        // Never show future dates as absent – cap to today
        if ($to->gt($today)) {
            $to = $today->copy();
        }

        // --- 1. Fetch real attendance rows ---
        $attendanceRows = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->where('is_overtime_session', false) // Only regular sessions for history view
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(fn($a) => Carbon::parse($a->date)->toDateString());

        // --- 2. Fetch approved / active leave records ---
        $leaveRows = collect();
        try {
            $leaveRows = Leave::where('employee_id', $employeeId)
                ->whereIn('status', ['approved', 'active'])
                ->where('start_date', '<=', $to->toDateString())
                ->where('end_date',   '>=', $from->toDateString())
                ->get();
        } catch (\Exception $e) {
            // Leave table may not exist in all environments – degrade gracefully
            Log::warning('buildFullHistory: Could not query leaves table', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
        }

        // Build a set of leave-covered dates for O(1) lookup
        $leaveDates = [];
        foreach ($leaveRows as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd   = Carbon::parse($leave->end_date);
            $cursor     = $leaveStart->copy();
            while ($cursor->lte($leaveEnd)) {
                $leaveDates[$cursor->toDateString()] = [
                    'leave_type'   => $leave->leave_type   ?? $leave->type   ?? 'Leave',
                    'leave_reason' => $leave->reason        ?? $leave->notes  ?? null,
                    'leave_id'     => $leave->id,
                ];
                $cursor->addDay();
            }
        }

        // --- 3. Walk every calendar day in the range ---
        $records = [];
        $summary = [
            'present_days' => 0,
            'late_days'    => 0,
            'absent_days'  => 0,
            'on_leave_days'=> 0,
            'total_hours'  => 0.0,
        ];

        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $dateStr = $cursor->toDateString();
            $isWeekend = $cursor->isWeekend();

            if (isset($attendanceRows[$dateStr])) {
                // ── Real attendance record ───────────────────────────
                $row    = $attendanceRows[$dateStr];
                $status = $row->status ?? 'present';

                $records[] = [
                    'id'           => $row->id,
                    'date'         => $dateStr,
                    'status'       => $status,
                    'clock_in'     => $row->clock_in,
                    'clock_out'    => $row->clock_out,
                    'hours_worked' => round((float)($row->total_hours ?? 0), 2),
                    'total_hours'  => round((float)($row->total_hours ?? 0), 2),
                    'regular_hours'  => round((float)($row->regular_hours ?? 0), 2),
                    'overtime_hours' => round((float)($row->overtime_hours ?? 0), 2),
                    'notes'        => $row->notes,
                    'is_weekend'   => $isWeekend,
                    'leave_type'   => null,
                    'leave_reason' => null,
                ];

                $summary['total_hours'] += (float)($row->total_hours ?? 0);

                if ($status === 'present' || $status === 'completed') {
                    $summary['present_days']++;
                } elseif ($status === 'late') {
                    $summary['late_days']++;
                    $summary['present_days']++; // late = worked
                } elseif ($status === 'on_leave') {
                    $summary['on_leave_days']++;
                } elseif ($status === 'absent') {
                    if (!$isWeekend) $summary['absent_days']++;
                }

            } elseif (isset($leaveDates[$dateStr])) {
                // ── No attendance row but leave is approved ──────────
                $leaveInfo = $leaveDates[$dateStr];

                $records[] = [
                    'id'             => null,
                    'date'           => $dateStr,
                    'status'         => 'on_leave',
                    'clock_in'       => null,
                    'clock_out'      => null,
                    'hours_worked'   => 0,
                    'total_hours'    => 0,
                    'regular_hours'  => 0,
                    'overtime_hours' => 0,
                    'notes'          => $leaveInfo['leave_reason'],
                    'is_weekend'     => $isWeekend,
                    'leave_type'     => $leaveInfo['leave_type'],
                    'leave_reason'   => $leaveInfo['leave_reason'],
                    'leave_id'       => $leaveInfo['leave_id'],
                ];

                if (!$isWeekend) {
                    $summary['on_leave_days']++;
                }

            } elseif (!$isWeekend) {
                // ── Weekday, no record, no leave → absent ────────────
                $records[] = [
                    'id'             => null,
                    'date'           => $dateStr,
                    'status'         => 'absent',
                    'clock_in'       => null,
                    'clock_out'      => null,
                    'hours_worked'   => 0,
                    'total_hours'    => 0,
                    'regular_hours'  => 0,
                    'overtime_hours' => 0,
                    'notes'          => null,
                    'is_weekend'     => false,
                    'leave_type'     => null,
                    'leave_reason'   => null,
                ];

                $summary['absent_days']++;
            }
            // Weekends with no record are simply skipped (not included in records)

            $cursor->addDay();
        }

        // Sort descending (most recent first) to match original behaviour
        usort($records, fn($a, $b) => strcmp($b['date'], $a['date']));

        $summary['total_hours'] = round($summary['total_hours'], 2);

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    // =========================================================================
    // EMPLOYEE – personal history
    // =========================================================================

    /**
     * Get attendance history for the currently authenticated employee.
     * Supports date range (date_from / date_to) OR month / year params.
     * Returns ALL statuses: present, late, absent, on_leave.
     *
     * GET /api/employee/attendance/history
     * GET /api/employee/attendance/?month=2&year=2026
     * GET /api/employee/attendance/?date_from=2026-01-01&date_to=2026-01-31
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            [$from, $to] = $this->resolveDateRange($request);

            Log::info('Fetching employee attendance history (full)', [
                'employee_id' => $employee->id,
                'from'        => $from->toDateString(),
                'to'          => $to->toDateString(),
            ]);

            $result = $this->buildFullHistory($employee->id, $from, $to);

            // Optional status filter (applied AFTER building full history)
            $statusFilter = $request->get('status');
            $records = $result['records'];
            if ($statusFilter) {
                $records = array_values(array_filter(
                    $records,
                    fn($r) => $r['status'] === $statusFilter
                ));
            }

            return response()->json([
                'success'    => true,
                'data'       => $records,
                'attendance' => $records, // alias for legacy consumers
                'summary'    => $result['summary'],
                'filters'    => [
                    'from'   => $from->toDateString(),
                    'to'     => $to->toDateString(),
                    'status' => $statusFilter,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance history', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance history',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // ADMIN / MANAGER – specific employee history
    // =========================================================================

    /**
     * Get full attendance history for a specific employee.
     * Accessible by admin (any employee) or manager (direct reports only).
     * Returns ALL statuses: present, late, absent, on_leave.
     *
     * Accepts both date range and month/year params:
     *   GET /api/admin/attendance/{employee}/history?date_from=2026-01-01&date_to=2026-01-31
     *   GET /api/admin/attendance/{employee}/history?month=1&year=2026
     *   GET /api/manager/attendance/{employee}/history?date_from=...
     */
    public function employeeHistory(Request $request, Employee $employee): JsonResponse
    {
        try {
            $user = $request->user();

            // ── Authorization ──────────────────────────────────────────────
            if ($user->role === 'manager') {
                $manager = $user->employee;

                if (!$manager) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Manager profile not found.',
                    ], 404);
                }

                $canAccess = $this->managerCanAccessEmployee($user, $manager, $employee);

                if (!$canAccess) {
                    Log::warning('Manager unauthorized access attempt', [
                        'manager_id'           => $manager->id,
                        'manager_user_id'      => $user->id,
                        'employee_id'          => $employee->id,
                        'employee_manager_id'  => $employee->manager_id,
                        'employee_business_id' => $employee->business_id,
                        'manager_business_id'  => $manager->business_id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to view this employee\'s attendance.',
                    ], 403);
                }
            }
            // Admins pass through without restriction

            // ── Date range ────────────────────────────────────────────────
            [$from, $to] = $this->resolveDateRange($request);

            Log::info('Fetching employee history (full status)', [
                'requester_role' => $user->role,
                'employee_id'    => $employee->id,
                'from'           => $from->toDateString(),
                'to'             => $to->toDateString(),
            ]);

            $result = $this->buildFullHistory($employee->id, $from, $to);

            return response()->json([
                'success' => true,
                'data'    => $result['records'],
                'summary' => $result['summary'],
                'employee' => [
                    'id'              => $employee->id,
                    'name'            => trim($employee->user->first_name . ' ' . $employee->user->last_name),
                    'employee_number' => $employee->employee_id,
                    'department'      => $employee->department,
                    'position'        => $employee->position,
                    'manager_id'      => $employee->manager_id,
                ],
                'period' => [
                    'from'  => $from->toDateString(),
                    'to'    => $to->toDateString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch employee history', [
                'employee_id' => $employee->id ?? null,
                'user_id'     => $request->user()->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch history',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Resolve the date range from request params.
     * Priority: date_from/date_to → month/year → current month
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->get('date_from') ?? $request->get('from');
        $dateTo   = $request->get('date_to')   ?? $request->get('to');

        if ($dateFrom && $dateTo) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        // Fall back to month/year
        $month = (int)($request->get('month', date('m')));
        $year  = (int)($request->get('year',  date('Y')));

        return [
            Carbon::create($year, $month, 1)->startOfDay(),
            Carbon::create($year, $month, 1)->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * Check whether a manager is permitted to view an employee's data.
     */
    private function managerCanAccessEmployee($user, $manager, Employee $employee): bool
    {
        // Direct assignment
        if ($employee->manager_id === $user->id) {
            return true;
        }

        // Same business + same department (employee has no other manager)
        if ($employee->business_id === $manager->business_id) {
            if ($manager->department_id) {
                if (
                    $employee->department_id === $manager->department_id &&
                    (!$employee->manager_id || $employee->manager_id === $user->id)
                ) {
                    return true;
                }
            } else {
                // Manager without department can access unassigned employees
                if (!$employee->manager_id) {
                    return true;
                }
            }
        }

        // Manager viewing own record
        if ($employee->id === $manager->id) {
            return true;
        }

        return false;
    }

    // =========================================================================
    // REMAINING METHODS (unchanged from original)
    // =========================================================================

    /**
     * Get monthly summary/stats
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found. Please contact HR.',
                ], 404);
            }

            $month = $request->get('month', date('m'));
            $year  = $request->get('year',  date('Y'));

            $summary = $this->attendanceService->getMonthlySummary($employee->id, $month, $year);

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_hours'     => $summary['total_hours'],
                    'regular_hours'   => $summary['regular_hours'],
                    'overtime_hours'  => $summary['overtime_hours'],
                    'attendance_rate' => $summary['attendance_rate'],
                    'late_days'       => $summary['late_days'],
                    'workdays'        => $summary['working_days'],
                    'working_days'    => $summary['working_days'],
                    'present_days'    => $summary['present_days'],
                    'absent_days'     => $summary['absent_days'],
                    'totalHours'      => $summary['total_hours'],
                    'regularHours'    => $summary['regular_hours'],
                    'overtimeHours'   => $summary['overtime_hours'],
                    'attendanceRate'  => $summary['attendance_rate'],
                    'lateDays'        => $summary['late_days'],
                    'totalWorkHours'  => $summary['total_hours'],
                    'regularWorkHours'   => $summary['regular_hours'],
                    'totalOvertimeHours' => $summary['overtime_hours'],
                ],
                'period' => [
                    'month'      => (int)$month,
                    'year'       => (int)$year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance summary', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary',
                'error'   => $e->getMessage(),
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
                return response()->json(['message' => 'Employee profile not found. Please contact HR.'], 404);
            }

            $attendance = $this->attendanceService->clockIn($employee);

            return response()->json([
                'success'    => true,
                'attendance' => $attendance,
                'message'    => 'Clocked in successfully at ' . $attendance->clock_in,
            ]);
        } catch (\Exception $e) {
            Log::error('Clock-in failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'hint'    => 'If you believe this is an error, try the "Reset Status" button.',
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
                return response()->json(['message' => 'Employee profile not found. Please contact HR.'], 404);
            }

            $attendance = $this->attendanceService->clockOut($employee);

            return response()->json([
                'success'    => true,
                'attendance' => $attendance,
                'message'    => 'Clocked out successfully. Total hours: ' . round($attendance->total_hours, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('Clock-out failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Clock in for overtime
     */
    public function clockInOvertime(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found. Please contact HR.',
                ], 404);
            }

            $attendance = $this->attendanceService->clockInOvertime($employee);
            $attendance->last_activity_at = now();
            $attendance->save();

            return response()->json([
                'success'          => true,
                'attendance'       => $attendance,
                'overtime_session' => $attendance,
                'message'          => 'Clocked in for overtime successfully at ' . $attendance->clock_in,
                'activity_tracking' => [
                    'enabled'                   => true,
                    'idle_threshold_minutes'    => config('attendance.idle_threshold_minutes', 15),
                    'heartbeat_interval_seconds'=> 60,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Overtime clock-in failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
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
            return response()->json(['status' => 'absent', 'message' => 'Employee profile not found']);
        }

        $statusData = $this->attendanceService->getTodayStatus($employee);

        // Log the status data for debugging
        Log::debug('Today status data from service', $statusData);

        // Calculate can_clock_out for backward compatibility
        $canClockOut = false;
        if (isset($statusData['can_clock_out_regular']) && $statusData['can_clock_out_regular']) {
            $canClockOut = true;
        }
        if (isset($statusData['can_clock_out_overtime']) && $statusData['can_clock_out_overtime']) {
            $canClockOut = true;
        }

        return response()->json([
            'success' => true,
            'status' => $statusData['status'] ?? 'absent',
            'regular_attendance' => $statusData['regular_attendance'] ?? null,
            'overtime_attendance' => $statusData['overtime_attendance'] ?? null,
            'overtime_session' => $statusData['overtime_attendance'] ?? null,
            'shift' => $statusData['shift'] ?? null,
            'can_clock_in' => $statusData['can_clock_in'] ?? false,
            'can_clock_out' => $canClockOut, // Backward compatibility
            'can_clock_out_regular' => $statusData['can_clock_out_regular'] ?? false,
            'can_clock_out_overtime' => $statusData['can_clock_out_overtime'] ?? false,
            'can_start_overtime' => $statusData['can_start_overtime'] ?? false,
            'is_in_overtime_session' => $statusData['is_in_overtime_session'] ?? false,
            'shift_has_ended' => $statusData['shift_has_ended'] ?? false,
            'expected_shift_end_time' => isset($statusData['expected_shift_end_time']) && $statusData['expected_shift_end_time']
                ? ($statusData['expected_shift_end_time'] instanceof \Carbon\Carbon 
                    ? $statusData['expected_shift_end_time']->format('H:i:s')
                    : $statusData['expected_shift_end_time'])
                : null,
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to get today status', [
            'user_id' => $request->user()->id, 
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'status' => 'absent',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Get overtime summary
     */
    public function overtimeSummary(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            $month = $request->get('month', date('m'));
            $year  = $request->get('year',  date('Y'));

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $overtimeSessions = $attendances->where('is_overtime_session', true);

            $summary = [
                'total_overtime_hours'   => round($attendances->sum('overtime_hours'), 2),
                'overtime_sessions_count'=> $overtimeSessions->count(),
                'days_with_overtime'     => $overtimeSessions->pluck('date')->unique()->count(),
                'total_regular_hours'    => round($attendances->sum('regular_hours'), 2),
                'total_all_hours'        => round($attendances->sum('total_hours'), 2),
                'breakdown_by_date'      => [],
            ];

            $byDate = $attendances->groupBy(fn($a) => Carbon::parse($a->date)->toDateString());
            foreach ($byDate as $date => $dayAttendances) {
                $regularAtt  = $dayAttendances->where('is_overtime_session', false)->first();
                $overtimeAtts= $dayAttendances->where('is_overtime_session', true);

                $summary['breakdown_by_date'][] = [
                    'date'                  => $date,
                    'regular_hours'         => $regularAtt ? round($regularAtt->regular_hours   ?? 0, 2) : 0,
                    'overtime_from_regular' => $regularAtt ? round($regularAtt->overtime_hours  ?? 0, 2) : 0,
                    'overtime_sessions'     => round($overtimeAtts->sum('total_hours'), 2),
                    'total_overtime'        => round(
                        ($regularAtt ? $regularAtt->overtime_hours : 0) + $overtimeAtts->sum('total_hours'),
                        2
                    ),
                    'total_hours'           => round($dayAttendances->sum('total_hours'), 2),
                ];
            }

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'period'  => [
                    'month'      => (int)$month,
                    'year'       => (int)$year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get overtime summary', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overtime summary',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force reset attendance status
     */
    public function forceReset(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['message' => 'Employee profile not found. Please contact HR.'], 404);
            }

            $closedCount = $this->attendanceService->forceCloseAllOpen($employee);

            return response()->json([
                'success'     => true,
                'message'     => "Successfully reset attendance status. $closedCount record(s) were auto-closed.",
                'closed_count'=> $closedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Force reset failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to reset status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get team attendance status for manager
     */
    public function getManagerTeamAttendance(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();
            $managerEmployee = $manager->employee;
            
            if (!$managerEmployee) {
                return response()->json(['success' => false, 'message' => 'Manager profile not found.'], 404);
            }
            
            $date       = $request->input('date', now()->toDateString());
            $targetDate = Carbon::parse($date)->startOfDay();
            
            $employeesQuery = $this->getBusinessScopedEmployees($request);
            $employeesQuery->with(['user']);
            $employees = $employeesQuery->get();
            
            $employeeIds = $employees->pluck('id');
            
            $attendances = Attendance::whereIn('employee_id', $employeeIds)
                ->whereDate('date', $targetDate)
                ->get()
                ->keyBy('employee_id');
            
            $attendanceData = [];
            $summary = [
                'present' => 0, 'absent' => 0, 'late' => 0,
                'onLeave' => 0, 'total_employees' => $employees->count(),
            ];
            
            foreach ($employees as $employee) {
                $attendance = $attendances->get($employee->id);
                
                $status    = 'absent';
                $clockIn   = null;
                $clockOut  = null;
                $totalHours= 0;
                
                if ($attendance) {
                    $status     = $attendance->status ?? 'present';
                    $clockIn    = $attendance->clock_in;
                    $clockOut   = $attendance->clock_out;
                    $totalHours = $attendance->total_hours ?? 0;
                }
                
                switch ($status) {
                    case 'present': case 'completed': $summary['present']++; break;
                    case 'late':    $summary['late']++;    break;
                    case 'on_leave':$summary['onLeave']++; break;
                    default:        $summary['absent']++;  break;
                }
                
                $attendanceData[] = [
                    'employee_id'     => $employee->id,
                    'employee_name'   => $employee->user->first_name . ' ' . $employee->user->last_name,
                    'employee_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                    'department'      => $employee->department ?? 'Unassigned',
                    'position'        => $employee->position ?? 'N/A',
                    'status'          => $status,
                    'clock_in'        => $clockIn,
                    'clock_out'       => $clockOut,
                    'total_hours'     => round($totalHours, 2),
                    'date'            => $targetDate->toDateString(),
                ];
            }
            
            return response()->json([
                'success'    => true,
                'attendances'=> $attendanceData,
                'summary'    => $summary,
                'date'       => $targetDate->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Manager Team Attendance Failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return response()->json(['success' => false, 'message' => 'Failed to fetch team attendance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get team status for manager
     */
    public function getTeamStatus(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $user            = $request->user();
            $managerEmployee = $user->employee;

            if (!$managerEmployee) {
                return response()->json([
                    'success'     => false,
                    'message'     => 'Manager profile not found.',
                    'attendances' => [],
                    'summary'     => $this->getEmptySummary(),
                ], 404);
            }

            if ($user->role !== 'manager') {
                return response()->json([
                    'success'     => false,
                    'message'     => 'Unauthorized.',
                    'attendances' => [],
                    'summary'     => $this->getEmptySummary(),
                ], 403);
            }

            $date       = $request->input('date', now()->toDateString());
            $targetDate = Carbon::parse($date)->startOfDay();

            $teamEmployees   = $this->getManagerTeamEmployees($user, $managerEmployee);
            $totalEmployees  = $teamEmployees->count();
            $teamEmployeeIds = $teamEmployees->pluck('id')->toArray();

            if ($totalEmployees === 0) {
                return response()->json([
                    'success'     => true,
                    'attendances' => [],
                    'summary'     => $this->getEmptySummary(),
                    'date'        => $targetDate->toDateString(),
                    'message'     => 'No team members assigned to you.',
                ]);
            }

            $attendances = Attendance::with(['employee.user', 'shiftAssignment'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereDate('date', $targetDate)
                ->get()
                ->keyBy('employee_id');

            $attendanceData = [];
            $summary = [
                'total_employees' => $totalEmployees,
                'present'   => 0, 'absent'       => 0,
                'late'      => 0, 'on_leave'      => 0,
                'completed' => 0, 'with_shifts'   => 0,
                'without_shifts' => 0,
            ];

            foreach ($teamEmployees as $employee) {
                if (!$employee->user) continue;

                $attendance      = $attendances->get($employee->id);
                $attendanceRecord= $this->processEmployeeAttendance($employee, $attendance, $targetDate, $summary);
                $attendanceData[]= $attendanceRecord;
            }

            usort($attendanceData, fn($a, $b) => strcmp($a['full_name'], $b['full_name']));

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success'      => true,
                'attendances'  => $attendanceData,
                'summary'      => $summary,
                'date'         => $targetDate->toDateString(),
                'manager_info' => [
                    'name'        => trim($managerEmployee->user->first_name . ' ' . $managerEmployee->user->last_name),
                    'business_id' => $managerEmployee->business_id,
                    'department'  => $managerEmployee->department,
                ],
                'debug' => config('app.debug') ? [
                    'execution_time_ms' => $executionTime,
                    'total_employees'   => $totalEmployees,
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('TEAM STATUS FAILED', [
                'user_id' => $request->user()->id ?? null,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team status',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get employees for manager's team
     */
    private function getManagerTeamEmployees($user, $managerEmployee)
    {
        $query = Employee::with(['user']);
        
        if ($managerEmployee->business_id) {
            $query->where('employees.business_id', $managerEmployee->business_id)
                  ->where('employees.manager_id', $user->id);
        } else {
            $query->whereNull('employees.business_id')
                  ->where('employees.manager_id', $user->id);
        }
        
        return $query->get();
    }

    /**
     * Process individual employee attendance record
     */
    private function processEmployeeAttendance($employee, $attendance, $targetDate, &$summary)
    {
        $status      = 'absent';
        $clockIn     = null;
        $clockOut    = null;
        $totalHours  = 0;
        $attendanceId= null;
        $shift       = null;
        $hasShift    = false;
        $expectedTime= '08:30:00';
        $isLate      = false;
        $minutesLate = 0;

        if ($attendance) {
            $attendanceId = $attendance->id;
            $status       = $attendance->status ?? 'present';
            $clockIn      = $attendance->clock_in;
            $clockOut     = $attendance->clock_out;
            $totalHours   = $attendance->total_hours ?? 0;
            
            $shift    = $attendance->shiftAssignment;
            $hasShift = $shift !== null;
            
            if ($hasShift) {
                $summary['with_shifts']++;
                $expectedTime = $shift->start_time;
                
                if ($clockIn) {
                    $shiftStart    = Carbon::parse($targetDate->toDateString() . ' ' . $shift->start_time);
                    $actualClockIn = Carbon::parse($targetDate->toDateString() . ' ' . $clockIn);
                    $gracePeriod   = (int)config('attendance.grace_period_minutes', 15);
                    $lateThreshold = $shiftStart->copy()->addMinutes($gracePeriod);

                    if ($actualClockIn->greaterThan($lateThreshold)) {
                        $isLate      = true;
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

        return [
            'id'              => $attendanceId,
            'employee_id'     => $employee->id,
            'full_name'       => trim($employee->user->first_name . ' ' . $employee->user->last_name),
            'employee_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
            'department'      => $employee->department ?? 'Unassigned',
            'position'        => $employee->position ?? 'N/A',
            'status'          => $status,
            'clock_in'        => $clockIn,
            'clock_out'       => $clockOut,
            'total_hours'     => round($totalHours, 2),
            'date'            => $targetDate->toDateString(),
            'has_shift'       => $hasShift,
            'shift'           => $shift ? [
                'id'         => $shift->id,
                'type'       => $shift->shift_type,
                'start_time' => $shift->start_time,
                'end_time'   => $shift->end_time,
                'status'     => $shift->status ?? 'active',
            ] : null,
            'expected_time'   => $expectedTime,
            'is_late'         => $isLate,
            'minutes_late'    => $minutesLate,
        ];
    }

    /**
     * Update summary counts based on status
     */
    private function updateSummaryStatus($status, &$summary)
    {
        switch ($status) {
            case 'completed': $summary['completed']++; $summary['present']++; break;
            case 'present':   $summary['present']++;   break;
            case 'late':      $summary['late']++;       $summary['present']++; break;
            case 'on_leave':  $summary['on_leave']++;   break;
            default:          $summary['absent']++;     break;
        }
    }

    /**
     * Get empty summary structure
     */
    private function getEmptySummary(): array
    {
        return [
            'total_employees' => 0,
            'present'         => 0,
            'absent'          => 0,
            'late'            => 0,
            'on_leave'        => 0,
            'completed'       => 0,
            'with_shifts'     => 0,
            'without_shifts'  => 0,
        ];
    }

    /**
     * Get manager's team attendance history
     */
    public function managerTeamHistory(Request $request): JsonResponse
    {
        try {
            $manager = $request->user()->employee;

            if (!$manager) {
                return response()->json(['success' => false, 'message' => 'Manager profile not found.'], 404);
            }

            $month      = $request->get('month', date('m'));
            $year       = $request->get('year',  date('Y'));
            $perPage    = $request->get('per_page', 50);
            $status     = $request->get('status');
            $employeeId = $request->get('employee_id');

            $teamQuery = Employee::query();
            
            if ($manager->business_id) {
                $teamQuery->where('business_id', $manager->business_id)
                          ->where('manager_id', $manager->user_id);
            } else {
                $teamQuery->whereNull('business_id')
                          ->where('manager_id', $manager->user_id);
            }
            
            $teamEmployeeIds = $teamQuery->pluck('id')->toArray();

            if (empty($teamEmployeeIds)) {
                return response()->json([
                    'success'          => true,
                    'data'             => [],
                    'pagination'       => ['current_page' => 1, 'per_page' => $perPage, 'total' => 0, 'last_page' => 1],
                    'employee_summaries' => [],
                    'team_info'        => ['total_members' => 0, 'department' => $manager->department, 'business_id' => $manager->business_id],
                    'message'          => 'No team members assigned to you.',
                ]);
            }

            $query = Attendance::with(['employee.user', 'shiftAssignment'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereYear('date', $year)
                ->whereMonth('date', $month);

            if ($employeeId) {
                if (!in_array($employeeId, $teamEmployeeIds)) {
                    return response()->json(['success' => false, 'message' => 'You do not have permission to view this employee\'s attendance.'], 403);
                }
                $query->where('employee_id', $employeeId);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('date', 'desc')->orderBy('employee_id', 'asc');

            $attendances = $query->paginate($perPage);

            $allRecords = Attendance::with(['employee.user'])
                ->whereIn('employee_id', $teamEmployeeIds)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
            
            $employeeSummaries = $allRecords->groupBy('employee_id')->map(function ($records) use ($manager) {
                $employee = $records->first()->employee;
                return [
                    'employee_id'   => $employee->id,
                    'name'          => trim($employee->user->first_name . ' ' . $employee->user->last_name),
                    'is_manager'    => $employee->id === $manager->id,
                    'total_hours'   => round($records->sum('total_hours'), 2),
                    'regular_hours' => round($records->sum('regular_hours'), 2),
                    'overtime_hours'=> round($records->sum('overtime_hours'), 2),
                    'present_days'  => $records->whereIn('status', ['present', 'completed'])->count(),
                    'absent_days'   => $records->where('status', 'absent')->count(),
                    'late_days'     => $records->where('status', 'late')->count(),
                ];
            })->values();

            $employeeSummaries = $employeeSummaries->sort(function ($a, $b) {
                if ($a['is_manager'] && !$b['is_manager']) return -1;
                if (!$a['is_manager'] && $b['is_manager']) return 1;
                return strcmp($a['name'], $b['name']);
            })->values();

            return response()->json([
                'success'           => true,
                'data'              => AttendanceResource::collection($attendances->items()),
                'pagination'        => [
                    'current_page' => $attendances->currentPage(),
                    'per_page'     => $attendances->perPage(),
                    'total'        => $attendances->total(),
                    'last_page'    => $attendances->lastPage(),
                ],
                'employee_summaries'=> $employeeSummaries,
                'team_info'         => [
                    'total_members'=> count($teamEmployeeIds),
                    'department'   => $manager->department,
                    'business_id'  => $manager->business_id,
                ],
                'filters' => [
                    'month'       => (int)$month,
                    'year'        => (int)$year,
                    'status'      => $status,
                    'employee_id' => $employeeId,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch team attendance history', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch team attendance history', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Manager clock out an employee
     */
    public function managerClockOut(Request $request, Employee $employee): JsonResponse
    {
        try {
            $manager = $request->user()->employee;

            if (!$manager) {
                return response()->json(['success' => false, 'message' => 'Manager profile not found.'], 404);
            }

            $canManage = false;
            if ($employee->manager_id === $request->user()->id) {
                $canManage = true;
            } elseif ($employee->business_id === $manager->business_id) {
                if ($manager->department_id) {
                    if ($employee->department_id === $manager->department_id && (!$employee->manager_id || $employee->manager_id === $request->user()->id)) {
                        $canManage = true;
                    }
                } else {
                    if (!$employee->manager_id) $canManage = true;
                }
            }

            if (!$canManage) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to clock out this employee.'], 403);
            }

            $today      = now()->toDateString();
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->first();

            if (!$attendance) {
                return response()->json(['success' => false, 'message' => 'No active clock-in found for this employee today.'], 404);
            }

            $attendance->clock_out = now()->format('H:i:s');
            $hours = $attendance->calculateHours();
            $attendance->total_hours   = $hours['total'];
            $attendance->regular_hours = $hours['regular'];
            $attendance->overtime_hours= $hours['overtime'];
            $attendance->status = 'present';
            $attendance->notes  = ($attendance->notes ? $attendance->notes . ' | ' : '')
                . "Clocked out by manager ({$manager->user->first_name} {$manager->user->last_name}) at " . now()->toDateTimeString();
            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => "{$employee->user->first_name} {$employee->user->last_name} clocked out successfully",
                'attendance' => [
                    'id'          => $attendance->id,
                    'employee_id' => $employee->id,
                    'clock_in'    => $attendance->clock_in,
                    'clock_out'   => $attendance->clock_out,
                    'total_hours' => round($attendance->total_hours, 2),
                    'status'      => $attendance->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Manager clock out failed', ['manager_id' => $request->user()->id, 'employee_id' => $employee->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to clock out employee', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get detailed history with overtime breakdown
     */
    public function detailedHistory(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            $month = $request->get('month', date('m'));
            $year  = $request->get('year',  date('Y'));

            $attendances = Attendance::where('employee_id', $employee->id)
                ->with(['shiftAssignment', 'parentAttendance'])
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'asc')
                ->get();

            $groupedByDate = [];
            foreach ($attendances as $attendance) {
                $date = Carbon::parse($attendance->date)->toDateString();
                
                if (!isset($groupedByDate[$date])) {
                    $groupedByDate[$date] = [
                        'date'        => $date,
                        'day_of_week' => Carbon::parse($date)->format('l'),
                        'regular_session'   => null,
                        'overtime_sessions' => [],
                        'totals' => ['regular_hours' => 0, 'overtime_hours' => 0, 'total_hours' => 0],
                    ];
                }

                $sessionData = [
                    'id'              => $attendance->id,
                    'clock_in'        => $attendance->clock_in,
                    'clock_out'       => $attendance->clock_out,
                    'total_hours'     => round($attendance->total_hours    ?? 0, 2),
                    'regular_hours'   => round($attendance->regular_hours  ?? 0, 2),
                    'overtime_hours'  => round($attendance->overtime_hours ?? 0, 2),
                    'status'          => $attendance->status,
                    'shift'           => $attendance->shiftAssignment ? [
                        'type'       => $attendance->shiftAssignment->shift_type,
                        'start_time' => $attendance->shiftAssignment->start_time,
                        'end_time'   => $attendance->shiftAssignment->end_time,
                    ] : null,
                ];

                if ($attendance->is_overtime_session) {
                    $groupedByDate[$date]['overtime_sessions'][] = $sessionData;
                } else {
                    $groupedByDate[$date]['regular_session'] = $sessionData;
                }

                $groupedByDate[$date]['totals']['regular_hours']  += $attendance->regular_hours  ?? 0;
                $groupedByDate[$date]['totals']['overtime_hours'] += $attendance->overtime_hours ?? 0;
                $groupedByDate[$date]['totals']['total_hours']    += $attendance->total_hours    ?? 0;
            }

            foreach ($groupedByDate as &$day) {
                $day['totals']['regular_hours']  = round($day['totals']['regular_hours'],  2);
                $day['totals']['overtime_hours'] = round($day['totals']['overtime_hours'], 2);
                $day['totals']['total_hours']    = round($day['totals']['total_hours'],    2);
            }

            return response()->json([
                'success' => true,
                'data'    => array_values($groupedByDate),
                'period'  => [
                    'month'      => (int)$month,
                    'year'       => (int)$year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get detailed history', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch detailed history', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get attendance history for a specific date
     */
    public function historyByDate(Request $request, string $date): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            try {
                $targetDate = Carbon::parse($date)->toDateString();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid date format.'], 422);
            }

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $targetDate)
                ->first();

            if (!$attendance) {
                return response()->json(['success' => false, 'message' => 'No attendance record found for this date.', 'date' => $targetDate], 404);
            }

            return response()->json(['success' => true, 'data' => new AttendanceResource($attendance), 'date' => $targetDate]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance by date', ['user_id' => $request->user()->id, 'date' => $date, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch attendance record', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin history with pagination
     */
    public function adminHistory(Request $request): JsonResponse
    {
        try {
            $employeeId = $request->get('employee_id');
            $month      = $request->get('month', date('m'));
            $year       = $request->get('year',  date('Y'));
            $perPage    = $request->get('per_page', 50);
            $status     = $request->get('status');
            $department = $request->get('department');

            $scopedEmployees = $this->getBusinessScopedEmployees($request)->pluck('id');

            $query = Attendance::with(['employee'])
                ->whereIn('employee_id', $scopedEmployees)
                ->whereYear('date', $year)
                ->whereMonth('date', $month);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            if ($department) {
                $query->whereHas('employee', fn($q) => $q->where('department', $department));
            }

            if ($status) {
                $query->where('status', $status);
            }

            $query->orderBy('date', 'desc')->orderBy('employee_id', 'asc');
            $attendances = $query->paginate($perPage);

            return response()->json([
                'success'    => true,
                'data'       => AttendanceResource::collection($attendances->items()),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'per_page'     => $attendances->perPage(),
                    'total'        => $attendances->total(),
                    'last_page'    => $attendances->lastPage(),
                ],
                'filters' => [
                    'employee_id' => $employeeId,
                    'month'       => (int)$month,
                    'year'        => (int)$year,
                    'status'      => $status,
                    'department'  => $department,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin history fetch failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch attendance history', 'error' => $e->getMessage()], 500);
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
                return response()->json(['message' => 'Employee profile not found.'], 404);
            }

            $attendance = Attendance::create([
                'employee_id'  => $employee->id,
                'date'         => $request->date,
                'clock_in'     => $request->clock_in,
                'clock_out'    => $request->clock_out,
                'break_minutes'=> $request->break_minutes ?? 0,
                'notes'        => $request->notes,
            ]);

            $attendance->total_hours = $attendance->calculateTotalHours();
            $attendance->save();

            return response()->json([
                'success'    => true,
                'attendance' => new AttendanceResource($attendance),
                'message'    => 'Attendance recorded successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store attendance', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to record attendance', 'error' => $e->getMessage()], 500);
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
                'clock_in'     => $request->clock_in,
                'clock_out'    => $request->clock_out,
                'break_minutes'=> $request->break_minutes ?? 0,
                'notes'        => $request->notes,
            ]);

            $attendance->total_hours = $attendance->calculateTotalHours();
            $attendance->save();

            return response()->json([
                'success'    => true,
                'attendance' => new AttendanceResource($attendance),
                'message'    => 'Attendance updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update attendance', ['user_id' => $request->user()->id, 'attendance_id' => $attendance->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update attendance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get attendance status for all employees on a date
     */
    public function getAttendanceStatus(Request $request): JsonResponse
    {
        try {
            $date       = $request->input('date', now()->toDateString());
            $countryId  = $request->input('country_id');
            $businessId = $request->input('business_id');
            $department = $request->input('department');
            
            $targetDate = Carbon::parse($date)->startOfDay();

            $employeesQuery = $this->getBusinessScopedEmployees($request);
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

            if ($countryId)  $employeesQuery->where('employees.country_id', $countryId);
            if ($businessId) $employeesQuery->where('employees.business_id', $businessId);
            if ($department) $employeesQuery->where('employees.department', $department);

            $employees = $employeesQuery->get();

            $attendancesQuery = Attendance::where('date', $targetDate->toDateString());
            if ($countryId)  $attendancesQuery->where('country_id', $countryId);
            if ($businessId) $attendancesQuery->where('business_id', $businessId);
            if ($department) $attendancesQuery->whereHas('employee', fn($q) => $q->where('department', $department));

            $attendances = $attendancesQuery->get()->keyBy('employee_id');

            $employeeCountryIds  = $employees->pluck('country_id')->filter()->unique()->values();
            $employeeBusinessIds = $employees->pluck('employee_business_id')->filter()->unique()->values();

            $countries  = Country::whereIn('id', $employeeCountryIds)->get()->keyBy('id');
            $businesses = Business::whereIn('id', $employeeBusinessIds)->get()->keyBy('id');

            $data         = [];
            $presentCount = 0;
            $absentCount  = 0;
            $lateCount    = 0;

            foreach ($employees as $employee) {
                $attendance = $attendances->get($employee->id);

                $status     = 'absent';
                $clockIn    = null;
                $clockOut   = null;
                $totalHours = 0;

                if ($attendance) {
                    $status     = $attendance->status ?? 'present';
                    $clockIn    = $attendance->clock_in;
                    $clockOut   = $attendance->clock_out;
                    $totalHours = $attendance->total_hours ?? 0;

                    if (in_array($status, ['present', 'completed'])) $presentCount++;
                    elseif ($status === 'late')     $lateCount++;
                    else                            $absentCount++;
                } else {
                    $absentCount++;
                }

                $country  = $countries->get($employee->country_id);
                $business = $businesses->get($employee->employee_business_id);

                $data[] = [
                    'employee_id'        => $employee->id,
                    'first_name'         => $employee->first_name,
                    'last_name'          => $employee->last_name,
                    'employee_id_number' => $employee->employee_id ?? 'EMP' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                    'department'         => $employee->department ?? 'Unassigned',
                    'position'           => $employee->position ?? 'N/A',
                    'country_id'         => $employee->country_id,
                    'business_id'        => $employee->employee_business_id,
                    'country_name'       => $country  ? $country->name  : null,
                    'business_name'      => $business ? $business->name : null,
                    'status'             => $status,
                    'clock_in'           => $clockIn,
                    'clock_out'          => $clockOut,
                    'total_hours'        => round($totalHours, 2),
                    'date'               => $targetDate->toDateString(),
                ];
            }

            $totalEmployees  = $employees->count();
            $attendanceRate  = $totalEmployees > 0
                ? round((($presentCount + $lateCount) / $totalEmployees) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data'    => $data,
                'summary' => [
                    'total_employees' => $totalEmployees,
                    'present_count'   => $presentCount,
                    'absent_count'    => $absentCount,
                    'late_count'      => $lateCount,
                    'attendance_rate' => $attendanceRate,
                ],
                'filters' => [
                    'date'        => $targetDate->toDateString(),
                    'country_id'  => $countryId,
                    'business_id' => $businessId,
                    'department'  => $department,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch attendance status', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch attendance status', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark employee present
     */
    public function markPresent(Request $request, Employee $employee): JsonResponse
    {
        try {
            $date = $request->input('date');

            if (!$date) {
                return response()->json(['success' => false, 'message' => 'Date is required.'], 422);
            }

            $attendanceDate = Carbon::parse($date)->startOfDay();
            $clockIn        = $request->input('clock_in', '08:00:00');
            $clockOut       = $request->input('clock_out', '17:00:00');

            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $attendanceDate)
                ->first();

            $adminNote = "Manually marked present by " . $request->user()->name . " on " . now()->toDateTimeString();

            if ($existingAttendance) {
                $existingAttendance->update([
                    'status'    => 'present',
                    'clock_in'  => $existingAttendance->clock_in  ?? $clockIn,
                    'clock_out' => $existingAttendance->clock_out ?? $clockOut,
                    'notes'     => ($existingAttendance->notes ? $existingAttendance->notes . ' | ' : '') . $adminNote,
                ]);

                $hours = $existingAttendance->calculateHours();
                $existingAttendance->total_hours    = $hours['total'];
                $existingAttendance->regular_hours  = $hours['regular'];
                $existingAttendance->overtime_hours = $hours['overtime'];
                $existingAttendance->save();

                $attendance = $existingAttendance;
            } else {
                $attendance = Attendance::create([
                    'employee_id'  => $employee->id,
                    'country_id'   => $employee->country_id,
                    'business_id'  => $employee->business_id,
                    'date'         => $attendanceDate,
                    'clock_in'     => $clockIn,
                    'clock_out'    => $clockOut,
                    'break_minutes'=> $request->input('break_minutes', 0),
                    'status'       => 'present',
                    'notes'        => $adminNote,
                ]);

                $hours = $attendance->calculateHours();
                $attendance->total_hours    = $hours['total'];
                $attendance->regular_hours  = $hours['regular'];
                $attendance->overtime_hours = $hours['overtime'];
                $attendance->save();
            }

            return response()->json([
                'success' => true,
                'data'    => new AttendanceResource($attendance),
                'message' => "Employee marked as present for {$attendanceDate->toDateString()}.",
            ]);

        } catch (\Exception $e) {
            Log::error('Mark present failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to mark employee as present', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get countries with employee counts
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
                ->groupBy('countries.id', 'countries.name', 'countries.code', 'countries.currency_code', 'countries.currency_symbol')
                ->orderBy('countries.name')
                ->get();

            return response()->json(['success' => true, 'data' => $countries]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch countries', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch countries: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get businesses with employee counts
     */
    public function getBusinesses(Request $request): JsonResponse
    {
        try {
            $user      = $request->user();
            $countryId = $request->get('country_id');
            $search    = $request->get('search');

            $query = Business::select(
                    'businesses.id',
                    'businesses.name',
                    'businesses.legal_name',
                    'businesses.country_id',
                    'countries.name as country_name',
                    DB::raw('COUNT(DISTINCT employees.id) as employee_count')
                )
                ->leftJoin('employees', function ($join) use ($user) {
                    $join->on('businesses.id', '=', 'employees.business_id');
                    if ($user->role === 'admin' && $user->current_business_id) {
                        $join->where('employees.business_id', $user->current_business_id);
                    }
                })
                ->leftJoin('countries', 'businesses.country_id', '=', 'countries.id')
                ->where('businesses.status', 'active');

            if ($countryId) $query->where('businesses.country_id', $countryId);

            if ($user->role === 'admin' && !$user->current_business_id && $user->current_country_code) {
                $query->where('countries.code', $user->current_country_code);
            }

            if ($search) {
                $query->where(fn($q) => $q->where('businesses.name', 'like', "%{$search}%")
                    ->orWhere('businesses.legal_name', 'like', "%{$search}%"));
            }

            $businesses = $query->groupBy(
                    'businesses.id', 'businesses.name', 'businesses.legal_name',
                    'businesses.country_id', 'countries.name'
                )
                ->orderBy('businesses.name')
                ->get();

            return response()->json(['success' => true, 'data' => $businesses]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch businesses', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch businesses: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bulk mark employees present
     */
    public function bulkMarkPresent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_ids'   => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'date'           => 'required|date',
                'clock_in'       => 'nullable|date_format:H:i:s',
                'clock_out'      => 'nullable|date_format:H:i:s',
            ]);

            $date     = Carbon::parse($validated['date'])->startOfDay();
            $clockIn  = $validated['clock_in']  ?? '08:00:00';
            $clockOut = $validated['clock_out'] ?? '17:00:00';

            $results      = [];
            $successCount = 0;
            $failureCount = 0;

            DB::beginTransaction();
            try {
                foreach ($validated['employee_ids'] as $employeeId) {
                    try {
                        $employee = Employee::findOrFail($employeeId);
                        
                        $attendance = Attendance::updateOrCreate(
                            ['employee_id' => $employee->id, 'date' => $date],
                            [
                                'clock_in'  => $clockIn,
                                'clock_out' => $clockOut,
                                'status'    => 'present',
                                'notes'     => 'Bulk marked present by admin on ' . now()->toDateTimeString(),
                            ]
                        );

                        $attendance->total_hours = $attendance->calculateTotalHours();
                        $attendance->save();

                        $results[] = ['employee_id' => $employee->id, 'name' => "{$employee->first_name} {$employee->last_name}", 'success' => true];
                        $successCount++;

                    } catch (\Exception $e) {
                        $results[] = ['employee_id' => $employeeId, 'success' => false, 'error' => $e->getMessage()];
                        $failureCount++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Bulk mark present completed: {$successCount} succeeded, {$failureCount} failed",
                    'results' => $results,
                    'summary' => ['total' => count($validated['employee_ids']), 'success' => $successCount, 'failed' => $failureCount],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Bulk mark present failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Bulk operation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get current statuses
     */
    public function currentStatuses(Request $request): JsonResponse
    {
        try {
            $today = now()->toDateString();
            
            $employees = Employee::with(['user'])
                ->leftJoin('attendances', function ($join) use ($today) {
                    $join->on('employees.id', '=', 'attendances.employee_id')
                         ->whereDate('attendances.date', $today);
                })
                ->select('employees.*', 'attendances.status', 'attendances.clock_in', 'attendances.clock_out')
                ->get();

            $data = $employees->map(fn($employee) => [
                'id'         => $employee->id,
                'first_name' => $employee->first_name,
                'last_name'  => $employee->last_name,
                'department' => $employee->department,
                'status'     => $employee->status ?? 'absent',
                'clock_in'   => $employee->clock_in,
                'clock_out'  => $employee->clock_out,
            ]);

            $presentCount = $data->whereIn('status', ['present', 'completed'])->count();
            $absentCount  = $data->where('status', 'absent')->count();

            return response()->json([
                'success' => true,
                'data'    => $data,
                'summary' => ['total' => $data->count(), 'present' => $presentCount, 'absent' => $absentCount],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch current statuses', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch current statuses', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get monthly stats
     */
    public function monthlyStats(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            $month = $request->get('month', date('m'));
            $year  = $request->get('year',  date('Y'));

            if ($month < 1 || $month > 12) {
                return response()->json(['success' => false, 'message' => 'Invalid month.'], 422);
            }

            $stats         = Attendance::getMonthlyStats($employee->id, $month, $year);
            $overtimeHours = Attendance::getMonthlyOvertimeHours($employee->id, $month, $year);
            $workingDays   = $this->getWorkingDaysInMonth($month, $year);
            $attendanceRate= $workingDays > 0 ? round(($stats['completed_days'] / $workingDays) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data'    => [
                    'total_hours'             => $stats['total_hours'],
                    'total_days_worked'       => $stats['completed_days'],
                    'average_hours_per_day'   => $stats['average_hours_per_day'],
                    'overtime_hours'          => $overtimeHours,
                    'present_days'            => $stats['present_days'],
                    'late_days'               => $stats['late_days'],
                    'absent_days'             => $workingDays - $stats['total_days'],
                    'incomplete_days'         => $stats['incomplete_days'],
                    'working_days_in_month'   => $workingDays,
                    'attendance_rate'         => $attendanceRate,
                ],
                'period' => [
                    'month'      => (int)$month,
                    'year'       => (int)$year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch monthly stats', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch monthly statistics', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get monthly breakdown by day
     */
    public function monthlyBreakdown(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            $month = $request->get('month', date('m'));
            $year  = $request->get('year',  date('Y'));

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'asc')
                ->get();

            $breakdown = $attendances->map(function ($attendance) {
                $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
                $overtimeHours = max(0, $attendance->total_hours - $overtimeThreshold);

                return [
                    'date'          => Carbon::parse($attendance->date)->toDateString(),
                    'day_of_week'   => Carbon::parse($attendance->date)->format('l'),
                    'clock_in'      => $attendance->clock_in,
                    'clock_out'     => $attendance->clock_out,
                    'total_hours'   => round($attendance->total_hours, 2),
                    'overtime_hours'=> round($overtimeHours, 2),
                    'break_minutes' => $attendance->break_minutes,
                    'status'        => $attendance->status,
                    'notes'         => $attendance->notes,
                ];
            });

            $totalHours   = $attendances->sum('total_hours');
            $totalOvertime= $attendances->sum(fn($a) => max(0, $a->total_hours - config('payroll.attendance.overtime_threshold', 8)));

            return response()->json([
                'success'   => true,
                'breakdown' => $breakdown,
                'summary'   => [
                    'total_hours'          => round($totalHours, 2),
                    'total_overtime'       => round($totalOvertime, 2),
                    'total_days'           => $breakdown->count(),
                    'average_daily_hours'  => $breakdown->count() > 0 ? round($totalHours / $breakdown->count(), 2) : 0,
                ],
                'period' => [
                    'month'      => (int)$month,
                    'year'       => (int)$year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch monthly breakdown', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to fetch monthly breakdown', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalculate hours
     */
    public function recalculateHours(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee profile not found.'], 404);
            }

            $month = $request->get('month');
            $year  = $request->get('year');

            $query = Attendance::where('employee_id', $employee->id)->whereNotNull('clock_out');

            if ($month && $year) {
                $query->whereMonth('date', $month)->whereYear('date', $year);
            }

            $records = $query->get();
            $updated = 0;

            foreach ($records as $record) {
                $oldHours = $record->total_hours;
                $newHours = $record->calculateTotalHours();

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
                    'total_records'    => $records->count(),
                    'updated_records'  => $updated,
                    'no_change_records'=> $records->count() - $updated,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to recalculate hours', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to recalculate hours', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: working days in month (excluding weekends)
     */
    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $days        = 0;
        $date        = Carbon::create($year, $month, 1);
        $daysInMonth = $date->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            if (!Carbon::create($year, $month, $day)->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }
}
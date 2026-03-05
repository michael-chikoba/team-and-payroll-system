<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\AttendanceResource;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ManagerController extends Controller
{
    /**
     * Helper to get the authenticated user's employee profile
     * to determine their Business and Country context.
     */
    private function getManagerProfile(Request $request): ?Employee
    {
        return Employee::where('user_id', $request->user()->id)->first();
    }

    public function employees(Request $request): JsonResponse
{
    $managerProfile = $this->getManagerProfile($request);

    if (!$managerProfile) {
        return response()->json([
            'data' => [],
            'message' => 'Manager profile not found'
        ]);
    }

    $employees = Employee::with(['user', 'business', 'country'])
        ->where('manager_id', $request->user()->id)
        ->sameContext($managerProfile)
        ->get();

    // Transform the data to match what Vue component expects
    $transformed = $employees->map(function ($employee) {
        return [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'full_name' => $employee->full_name,
            'first_name' => $employee->user->first_name ?? null,
            'last_name' => $employee->user->last_name ?? null,
            'email' => $employee->user->email ?? null,
            'position' => $employee->position,
            'department' => $employee->department,
            'employment_type' => $employee->employment_type,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'phone' => $employee->phone, // Auto-decrypts
            'address' => $employee->address,
            'emergency_contact' => $employee->emergency_contact,
            'location' => $employee->address ? 'Office' : 'Remote', // You can customize this
            'reports_to' => $this->getManagerName($employee->manager_id),
            'user' => $employee->user ? [
                'id' => $employee->user->id,
                'name' => $employee->user->first_name . ' ' . $employee->user->last_name,
                'email' => $employee->user->email,
                'first_name' => $employee->user->first_name,
                'last_name' => $employee->user->last_name,
            ] : null,
            'business' => $employee->business ? [
                'id' => $employee->business->id,
                'name' => $employee->business->name,
            ] : null,
        ];
    });

    Log::info('MANAGER_CONTROLLER: Team employees fetched', [
        'manager_id' => $request->user()->id,
        'count' => $transformed->count()
    ]);

    return response()->json([
        'data' => $transformed,
        'meta' => [
            'total' => $transformed->count()
        ]
    ]);
}

/**
 * Helper to get manager name
 */
private function getManagerName($managerId): string
{
    if (!$managerId) return 'N/A';
    
    $manager = \App\Models\User::find($managerId);
    return $manager ? $manager->first_name . ' ' . $manager->last_name : 'N/A';
}

    public function employeeDetails(Employee $employee, Request $request): JsonResponse
    {
        $managerProfile = $this->getManagerProfile($request);

        // Check 1: Is the employee assigned to this manager?
        // Check 2: Do they belong to the same Business and Country?
        if (
            !$managerProfile ||
            $employee->manager_id !== $request->user()->id || 
            $employee->business_id !== $managerProfile->business_id ||
            $employee->country_id !== $managerProfile->country_id
        ) {
            return response()->json(['message' => 'Unauthorized access to this employee'], 403);
        }

        $employee->load(['user', 'leaves', 'attendances', 'payslips.payroll']);

        return response()->json([
            'employee' => new EmployeeResource($employee),
            'attendance_summary' => $this->getEmployeeAttendanceSummary($employee, $request),
            'leave_balances' => $this->getEmployeeLeaveBalances($employee),
        ]);
    }

   public function attendanceReport(Request $request): JsonResponse
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'attendances' => [],
                'summary' => $this->getEmptySummary(),
            ], 401);
        }

        // Get manager's employee profile
        $managerProfile = $user->employee;

        if (!$managerProfile) {
            Log::warning('Manager profile not found', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            
            return response()->json([
                'message' => 'Manager profile not found',
                'attendances' => [],
                'summary' => $this->getEmptySummary(),
            ], 404);
        }

        $managerId = $user->id;
        $month = $request->get('month', now()->format('m'));
        $year = $request->get('year', now()->format('Y'));
        $businessId = $managerProfile->business_id;

        Log::info('Processing attendance report', [
            'manager_id' => $managerId,
            'business_id' => $businessId,
            'month' => $month,
            'year' => $year
        ]);

        // Get team employees with their users loaded
        $teamEmployees = Employee::with('user')
            ->where('manager_id', $managerId)
            ->where('business_id', $businessId)
            ->get();

        $teamEmployeeIds = $teamEmployees->pluck('id')->toArray();

        if (empty($teamEmployeeIds)) {
            Log::info('No team members found for manager', [
                'manager_id' => $managerId,
                'business_id' => $businessId
            ]);
            
            return response()->json([
                'attendances' => [],
                'summary' => $this->getEmptySummary(),
                'message' => 'No team members found'
            ]);
        }

        // Query attendances for team members
        $attendances = Attendance::with(['employee.user'])
            ->whereIn('employee_id', $teamEmployeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->orderBy('employee_id', 'asc')
            ->get();

        Log::info('Attendances retrieved', [
            'count' => $attendances->count(),
            'team_size' => count($teamEmployeeIds)
        ]);

        // Calculate summary
        $summary = $this->getTeamAttendanceSummary($teamEmployeeIds, $month, $year);

        return response()->json([
            'attendances' => AttendanceResource::collection($attendances),
            'summary' => $summary,
            'period' => [
                'month' => $month,
                'year' => $year,
                'team_size' => count($teamEmployeeIds)
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Attendance report error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to generate attendance report',
            'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error',
            'attendances' => [],
            'summary' => $this->getEmptySummary(),
        ], 500);
    }
}

/**
 * Get team attendance summary for the specified period
 * Updated to match the existing method signature in ManagerController
 */
private function getTeamAttendanceSummary($employeeIdsOrManagerId, $monthOrManagerProfile, $year = null, $actualMonth = null): array
{
    try {
        // Handle both old signature (int $managerId, Employee $managerProfile, int $month, int $year)
        // and new signature (array $employeeIds, string $month, string $year)
        
        if (is_array($employeeIdsOrManagerId)) {
            // New signature: array $employeeIds, string $month, string $year
            $employeeIds = $employeeIdsOrManagerId;
            $month = $monthOrManagerProfile;
            $yearValue = $year;
        } else {
            // Old signature: int $managerId, Employee $managerProfile, int $month, int $year
            $managerId = $employeeIdsOrManagerId;
            $managerProfile = $monthOrManagerProfile;
            $month = $year;
            $yearValue = $actualMonth;
            
            // Fetch employees with strict context check
            $employees = Employee::where('manager_id', $managerId)
                ->sameContext($managerProfile)
                ->get();
                
            $employeeIds = $employees->pluck('id')->toArray();
        }

        if (empty($employeeIds)) {
            return $this->getEmptySummary();
        }

        $attendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $yearValue)
            ->whereMonth('date', $month)
            ->get();

        $totalRecords = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $totalHours = $attendances->sum('total_hours');
        
        // Calculate overtime hours (assuming 8-hour workday)
        $overtimeHours = $attendances->sum(function ($attendance) {
            return max(0, ($attendance->total_hours ?? 0) - 8);
        });

        // Calculate attendance rate
        $workingDays = $totalRecords;
        $attendanceRate = $workingDays > 0 ? ($presentDays / $workingDays) * 100 : 0;

        return [
            'total_employees' => count($employeeIds),
            'avg_attendance_rate' => round($attendanceRate, 2),
            'total_present_days' => $presentDays,
            'total_absent_days' => $absentDays,
            'total_late_days' => $lateDays,
            'total_records' => $totalRecords,
            'total_hours' => round((float) $totalHours, 2),
            'overtime_hours' => round((float) $overtimeHours, 2),
            'average_hours_per_day' => $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0,
        ];

    } catch (\Exception $e) {
        Log::error('Error calculating team attendance summary: ' . $e->getMessage());
        return $this->getEmptySummary();
    }
}



    private function getEmployeeAttendanceSummary(Employee $employee, Request $request): array
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $attendances = $employee->attendances()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'overtime_hours' => $attendances->sum(function ($attendance) {
                return max(0, ($attendance->total_hours ?? 0) - 8);
            }),
        ];
    }

    private function getEmployeeLeaveBalances(Employee $employee): array
    {
        return $employee->leaveBalances->mapWithKeys(function ($balance) {
            return [$balance->type => $balance->balance];
        })->toArray();
    }

  
    private function getEmptySummary(): array
    {
        return [
            'total_employees' => 0,
            'avg_attendance_rate' => 0,
            'total_present_days' => 0,
            'total_absent_days' => 0,
            'total_late_days' => 0,
        ];
    }
}
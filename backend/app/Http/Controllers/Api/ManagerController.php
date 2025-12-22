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

    public function employees(Request $request): AnonymousResourceCollection
    {
        $managerProfile = $this->getManagerProfile($request);

        if (!$managerProfile) {
            // Return empty collection if the manager has no employee profile
            return EmployeeResource::collection(collect([]));
        }

        $employees = Employee::with(['user', 'attendances' => function ($query) use ($request) {
            if ($request->has('month')) {
                $query->whereYear('date', $request->get('year', date('Y')))
                      ->whereMonth('date', $request->get('month', date('m')));
            }
        }])
        ->where('manager_id', $request->user()->id)
        // STRICT CHECK: Must match Manager's Business and Country
        ->sameContext($managerProfile)
        ->get();

        return EmployeeResource::collection($employees);
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
        $managerProfile = $this->getManagerProfile($request);

        if (!$managerProfile) {
             return response()->json([
                'attendances' => [],
                'summary' => $this->getEmptySummary(),
            ]);
        }

        $managerId = $request->user()->id;
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Query Attendances where the related Employee is managed by current user
        // AND belongs to the same Business/Country
        $attendances = Attendance::with(['employee.user'])
            ->whereHas('employee', function ($query) use ($managerId, $managerProfile) {
                $query->where('manager_id', $managerId)
                      ->sameContext($managerProfile); // Apply strict context scope
            })
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->get();

        $summary = $this->getTeamAttendanceSummary($managerId, $managerProfile, $month, $year);

        return response()->json([
            'attendances' => AttendanceResource::collection($attendances),
            'summary' => $summary,
        ]);
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

    private function getTeamAttendanceSummary(int $managerId, Employee $managerProfile, int $month, int $year): array
    {
        // Fetch employees with strict context check
        $employees = Employee::where('manager_id', $managerId)
            ->sameContext($managerProfile)
            ->get();
            
        $totalEmployees = $employees->count();

        if ($totalEmployees === 0) {
            return $this->getEmptySummary();
        }

        $totalPresent = 0;
        $totalAbsent = 0;
        $totalLate = 0;
        $totalWorkingDays = 0;

        foreach ($employees as $employee) {
            $attendances = $employee->attendances()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $totalPresent += $attendances->where('status', 'present')->count();
            $totalAbsent += $attendances->where('status', 'absent')->count();
            $totalLate += $attendances->where('status', 'late')->count();
            $totalWorkingDays += $attendances->count();
        }

        $avgAttendanceRate = $totalWorkingDays > 0 ? ($totalPresent / $totalWorkingDays) * 100 : 0;

        return [
            'total_employees' => $totalEmployees,
            'avg_attendance_rate' => round($avgAttendanceRate, 2),
            'total_present_days' => $totalPresent,
            'total_absent_days' => $totalAbsent,
            'total_late_days' => $totalLate,
        ];
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
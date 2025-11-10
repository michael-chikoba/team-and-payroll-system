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
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function employees(Request $request): AnonymousResourceCollection
    {
        $employees = Employee::with(['user', 'attendances' => function ($query) use ($request) {
            if ($request->has('month')) {
                $query->whereYear('date', $request->get('year', date('Y')))
                      ->whereMonth('date', $request->get('month', date('m')));
            }
        }])
        ->where('manager_id', $request->user()->id)
        ->get();

        return EmployeeResource::collection($employees);
    }

    public function employeeDetails(Employee $employee, Request $request): JsonResponse
    {
        // Check if employee is under this manager
        if ($employee->manager_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
        $managerId = $request->user()->id;
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $attendances = Attendance::with(['employee.user'])
            ->whereHas('employee', function ($query) use ($managerId) {
                $query->where('manager_id', $managerId);
            })
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->get();

        $summary = $this->getTeamAttendanceSummary($managerId, $month, $year);

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

    private function getTeamAttendanceSummary(int $managerId, int $month, int $year): array
    {
        $employees = Employee::where('manager_id', $managerId)->get();
        $totalEmployees = $employees->count();

        if ($totalEmployees === 0) {
            return [
                'total_employees' => 0,
                'avg_attendance_rate' => 0,
                'total_present_days' => 0,
                'total_absent_days' => 0,
                'total_late_days' => 0,
            ];
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
}
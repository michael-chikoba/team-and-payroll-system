<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
class DashboardController extends Controller
{
    private const SETTINGS_FILE = 'settings.json';
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
         
        if ($user->isAdmin()) {
            return $this->adminDashboard($request);
        } elseif ($user->isManager()) {
            return $this->managerDashboard($request);
        } else {
            return $this->employeeDashboard($request);
        }
    }
    private function employeeDashboard(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
         
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $attendanceSummary = $this->getEmployeeAttendanceSummary($employee->id, $currentMonth, $currentYear);
        $leaveBalances = $this->getEmployeeLeaveBalances($employee);
        $stats = [
            'attendance_summary' => $attendanceSummary,
            'leave_balances' => $leaveBalances,
            'recent_leaves' => Leave::where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'type' => $leave->type,
                        'start_date' => $leave->start_date,
                        'end_date' => $leave->end_date,
                        'number_of_days' => $leave->total_days,
                        'status' => $leave->status,
                        'reason' => $leave->reason,
                    ];
                }),
            'upcoming_payslip' => $this->getUpcomingPayslip($employee->id),
        ];
        return response()->json([
            'role' => 'employee',
            'stats' => $stats,
        ]);
    }
    private function getEmployeeLeaveBalances(Employee $employee): array
    {
        $currentYear = now()->year;
         
        // Get or create leave balances for the current year
        $leaveTypes = ['annual', 'sick', 'maternity', 'paternity'];
        $balances = [];
         
        foreach ($leaveTypes as $type) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'type' => $type,
                    'year' => $currentYear
                ],
                [
                    'balance' => $this->getDefaultLeaveBalance($type)
                ]
            );
             
            $balances[$type] = (float) $balance->balance;
        }
         
        return $balances;
    }
    private function getDefaultLeaveBalance(string $type): float
    {
        $settingsFile = storage_path('app/' . self::SETTINGS_FILE);
        $defaultBalances = [
            'annual' => 0,
            'sick' => 0,
            'maternity' => 0,
            'paternity' => 0,
        ];
        try {
            if (File::exists($settingsFile)) {
                $settings = json_decode(File::get($settingsFile), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($settings)) {
                    $defaultBalances['annual'] = $settings['annual_leave_days'] ?? 0;
                    $defaultBalances['sick'] = $settings['sick_leave_days'] ?? 0;
                    $defaultBalances['maternity'] = $settings['maternity_leave_days'] ?? 0;
                    $defaultBalances['paternity'] = $settings['paternity_leave_days'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error reading settings file for leave defaults: ' . $e->getMessage());
        }
        return $defaultBalances[$type] ?? 0;
    }
    private function getEmployeeAttendanceSummary($employeeId, $month, $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();
        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $totalHours = $attendances->sum('total_hours');
        $overtimeHours = $attendances->sum(function ($attendance) {
            return max(0, ($attendance->total_hours ?? 0) - 8);
        });
        return [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'total_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
        ];
    }
    private function getUpcomingPayslip($employeeId): ?array
    {
        $nextPayroll = Payroll::where('status', 'draft')
            ->orWhere('status', 'processing')
            ->orderBy('start_date', 'asc')
            ->first();
        if (!$nextPayroll) {
            return null;
        }
        return [
            'payroll_period' => $nextPayroll->payroll_period,
            'processing_date' => $nextPayroll->end_date,
            'estimated_days' => now()->diffInDays(Carbon::parse($nextPayroll->end_date)),
        ];
    }
    // Other methods (adminDashboard, managerDashboard, etc.) remain the same
    private function adminDashboard(Request $request): JsonResponse
    {
        $stats = [
            'total_employees' => Employee::count(),
            'total_managers' => \App\Models\User::where('role', 'manager')->count(),
            'pending_leaves' => Leave::where('status', 'pending')->count(),
            'recent_payrolls' => Payroll::orderBy('created_at', 'desc')->take(5)->get(),
            'attendance_today' => Attendance::whereDate('date', today())->count(),
            'system_health' => $this->getSystemHealth(),
        ];
        return response()->json([
            'role' => 'admin',
            'stats' => $stats,
            'recent_activity' => $this->getRecentActivity(),
        ]);
    }
    private function managerDashboard(Request $request): JsonResponse
    {
        $managerId = $request->user()->id;
         
        $teamEmployees = Employee::where('manager_id', $managerId)->get();
        $teamEmployeeIds = $teamEmployees->pluck('id');
        $stats = [
            'team_size' => $teamEmployees->count(),
            'pending_leave_approvals' => Leave::where('manager_id', $managerId)
                ->where('status', 'pending')
                ->count(),
            'team_attendance_today' => Attendance::whereIn('employee_id', $teamEmployeeIds)
                ->whereDate('date', today())
                ->count(),
            'team_productivity' => $this->getTeamProductivity($teamEmployeeIds),
        ];
        return response()->json([
            'role' => 'manager',
            'stats' => $stats,
            'pending_leaves' => Leave::with('employee.user')
                ->where('manager_id', $managerId)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get(),
        ]);
    }
    private function getSystemHealth(): array
    {
        return [
            'database' => 'healthy',
            'storage' => 'healthy',
            'memory' => 'healthy',
            'cpu' => 'healthy',
        ];
    }
    private function getRecentActivity(): array
    {
        return \App\Models\AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
                ->get()
            ->map(function ($log) {
                return [
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user->name,
                    'timestamp' => $log->created_at,
                ];
            })
            ->toArray();
    }
    private function getTeamProductivity($employeeIds): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $attendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->get();
        $totalWorkingDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $totalHours = $attendances->sum('total_hours');
        return [
            'attendance_rate' => $totalWorkingDays > 0 ? ($presentDays / $totalWorkingDays) * 100 : 0,
            'average_hours_per_day' => $presentDays > 0 ? $totalHours / $presentDays : 0,
            'total_hours' => $totalHours,
        ];
    }
}
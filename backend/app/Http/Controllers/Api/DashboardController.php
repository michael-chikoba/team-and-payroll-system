<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'role' => 'guest'
                ], 401);
            }
            
            Log::info('Dashboard accessed by user', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            
            if ($user->isAdmin()) {
                return $this->adminDashboard($request);
            } elseif ($user->isManager()) {
                return $this->managerDashboard($request);
            } else {
                return $this->employeeDashboard($request);
            }
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Server error',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function employeeDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found',
                'role' => 'employee'
            ], 404);
        }

        Log::info('Processing employee dashboard', [
            'employee_id' => $employee->id,
            'business_id' => $employee->business_id,
            'name' => $user->name
        ]);

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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $employee->employee_id
            ]
        ]);
    }

   
    private function getEmployeeLeaveBalances(Employee $employee): array
    {
        try {
            $currentYear = now()->year;
            $businessId = $employee->business_id;
            $countryCode = $employee->getCountryCode();
            
            Log::info('Getting leave balances for dashboard', [
                'employee_id' => $employee->id,
                'business_id' => $businessId,
                'country_code' => $countryCode,
                'year' => $currentYear
            ]);

            // Use the LeaveBalanceService for consistency
            $leaveBalanceService = app(\App\Services\LeaveBalanceService::class);
            
            // Get all balances from the service
            $balances = $leaveBalanceService->getAllBalances($employee->id);
            
            Log::info('Leave balances retrieved from service', [
                'employee_id' => $employee->id,
                'balances' => $balances
            ]);
            
            return $balances;
            
        } catch (\Exception $e) {
            Log::error('Error getting leave balances for dashboard: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty balances on error
            return [];
        }
    }

    private function getEmployeeAttendanceSummary($employeeId, $month, $year): array
    {
        try {
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
                'total_hours' => (float) $totalHours,
                'overtime_hours' => (float) $overtimeHours,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting attendance summary: ' . $e->getMessage());
            return [
                'present_days' => 0,
                'absent_days' => 0,
                'late_days' => 0,
                'total_hours' => 0,
                'overtime_hours' => 0,
            ];
        }
    }

    private function getUpcomingPayslip($employeeId): ?array
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error getting upcoming payslip: ' . $e->getMessage());
            return null;
        }
    }

    private function adminDashboard(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Admin dashboard error: ' . $e->getMessage());
            return response()->json([
                'role' => 'admin',
                'stats' => [],
                'error' => 'Failed to load admin dashboard'
            ], 500);
        }
    }

    private function managerDashboard(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Manager dashboard error: ' . $e->getMessage());
            return response()->json([
                'role' => 'manager',
                'stats' => [],
                'error' => 'Failed to load manager dashboard'
            ], 500);
        }
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Error getting recent activity: ' . $e->getMessage());
            return [];
        }
    }

    private function getTeamProductivity($employeeIds): array
    {
        try {
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
                'total_hours' => (float) $totalHours,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting team productivity: ' . $e->getMessage());
            return [
                'attendance_rate' => 0,
                'average_hours_per_day' => 0,
                'total_hours' => 0,
            ];
        }
    }
}
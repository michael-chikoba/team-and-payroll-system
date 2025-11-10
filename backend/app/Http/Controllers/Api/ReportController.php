<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportGeneratorService;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportGeneratorService $reportService)
    {
    }

    public function teamReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'department' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $reportData = $this->generateTeamReport($filters);

        return response()->json($reportData);
    }

    public function payrollReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:draft,processing,completed,failed',
        ]);

        $reportData = $this->reportService->generatePayrollReport($filters);

        return response()->json($reportData);
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:present,absent,late,half_day',
        ]);

        $reportData = $this->reportService->generateAttendanceReport($filters);

        return response()->json($reportData);
    }

    public function leaveReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'type' => 'sometimes|in:vacation,sick,personal,maternity,paternity',
            'status' => 'sometimes|in:pending,approved,rejected',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $reportData = $this->reportService->generateLeaveReport($filters);

        return response()->json($reportData);
    }

    public function productivityReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
        ]);

        $reportData = $this->generateProductivityReport($filters);

        return response()->json($reportData);
    }

    public function exportReport(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
        ]);

        $filters = $request->only(['start_date', 'end_date']);

        switch ($type) {
            case 'payroll':
                $data = $this->reportService->generatePayrollReport($filters);
                $filename = "payroll-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                break;
                
            case 'attendance':
                $data = $this->reportService->generateAttendanceReport($filters);
                $filename = "attendance-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                break;
                
            case 'leave':
                $data = $this->reportService->generateLeaveReport($filters);
                $filename = "leave-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                break;
                
            case 'team':
                $data = $this->generateTeamReport($filters);
                $filename = "team-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                break;
                
            case 'productivity':
                $data = $this->generateProductivityReport($filters);
                $filename = "productivity-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                break;
                
            default:
                return response()->json(['message' => 'Invalid report type'], 422);
        }

        if ($request->format === 'pdf') {
            return $this->reportService->exportToPdf("reports.{$type}", $data, $filename);
        } else {
            return $this->reportService->exportToCsv($data['data'] ?? [], $filename);
        }
    }

    /**
     * Generate team report data
     */
    private function generateTeamReport(array $filters): array
    {
        // Get team members based on department filter
        $query = Employee::with(['user', 'attendances', 'leaves']);
        
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        $employees = $query->get();
        
        // Calculate team statistics
        $totalEmployees = $employees->count();
        
        // Calculate present employees for today
        $today = now()->format('Y-m-d');
        $presentToday = Attendance::whereDate('date', $today)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'present')
            ->count();

        // Calculate pending leaves
        $pendingLeaves = Leave::whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'pending')
            ->count();

        // Calculate average productivity
        $totalProductivity = 0;
        $employeesWithProductivity = 0;

        foreach ($employees as $employee) {
            $productivity = $this->calculateEmployeeProductivity($employee, $filters);
            if ($productivity !== null) {
                $totalProductivity += $productivity;
                $employeesWithProductivity++;
            }
        }

        $avgProductivity = $employeesWithProductivity > 0 ? round($totalProductivity / $employeesWithProductivity) : 0;

        return [
            'data' => [
                'department' => $filters['department'] ?? 'All Departments',
                'period_start' => $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
                'period_end' => $filters['end_date'] ?? now()->format('Y-m-d'),
                'total_employees' => $totalEmployees,
                'active_employees' => $totalEmployees, // Since all employees are considered active in your current structure
                'present_today' => $presentToday,
                'on_leave' => $pendingLeaves,
                'avg_productivity' => $avgProductivity,
                'team_members' => $employees->map(function ($employee) use ($filters) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'email' => $employee->email,
                        'department' => $employee->department,
                        'position' => $employee->position ?? 'N/A',
                        'status' => 'active', // Default status
                        'productivity' => $this->calculateEmployeeProductivity($employee, $filters),
                        'last_attendance' => $employee->attendances->sortByDesc('date')->first()->date ?? 'N/A',
                    ];
                })
            ],
            'summary' => [
                'team_size' => $totalEmployees,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves,
                'avg_productivity' => $avgProductivity,
                'attendance_rate' => $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0,
            ]
        ];
    }

    /**
     * Generate productivity report data
     */
    private function generateProductivityReport(array $filters): array
    {
        $query = Employee::with(['user', 'attendances']);
        
        if (isset($filters['employee_id'])) {
            $query->where('id', $filters['employee_id']);
        }
        
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        $employees = $query->get();

        $reportData = $employees->map(function ($employee) use ($filters) {
            $productivity = $this->calculateEmployeeProductivity($employee, $filters);
            
            // Calculate tasks completed (you can replace this with actual task logic)
            $tasksCompleted = $this->calculateTaskCompletionRate($employee, $filters);

            // Calculate attendance rate for period
            $attendanceRate = $this->calculateAttendanceRate($employee, $filters);

            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'email' => $employee->email,
                'department' => $employee->department,
                'position' => $employee->position ?? 'N/A',
                'productivity_score' => $productivity,
                'tasks_completed' => $tasksCompleted,
                'attendance_rate' => $attendanceRate,
                'status' => 'active',
            ];
        });

        $totalProductivity = $reportData->sum('productivity_score');
        $avgProductivity = $reportData->count() > 0 ? round($totalProductivity / $reportData->count()) : 0;
        $totalTasksCompleted = $reportData->sum('tasks_completed');
        $avgAttendanceRate = $reportData->count() > 0 ? round($reportData->avg('attendance_rate')) : 0;

        return [
            'data' => $reportData,
            'summary' => [
                'total_employees' => $reportData->count(),
                'avg_productivity' => $avgProductivity,
                'total_tasks_completed' => $totalTasksCompleted,
                'avg_attendance_rate' => $avgAttendanceRate,
                'generated_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Calculate employee productivity score (0-100)
     */
    private function calculateEmployeeProductivity(Employee $employee, array $filters): int
    {
        $score = 0;
        $factors = 0;

        // Factor 1: Attendance (weight: 40%)
        $attendanceRate = $this->calculateAttendanceRate($employee, $filters);
        $score += $attendanceRate * 0.4;
        $factors++;

        // Factor 2: Task completion (weight: 40%)
        $taskCompletionRate = $this->calculateTaskCompletionRate($employee, $filters);
        $score += $taskCompletionRate * 0.4;
        $factors++;

        // Factor 3: Punctuality (weight: 20%)
        $punctualityRate = $this->calculatePunctualityRate($employee, $filters);
        $score += $punctualityRate * 0.2;
        $factors++;

        return min(100, max(0, round($score)));
    }

    /**
     * Calculate attendance rate for an employee
     */
    private function calculateAttendanceRate(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances();
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }

        $totalDays = $attendanceQuery->count();
        $presentDays = $attendanceQuery->where('status', 'present')->count();

        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;
    }

    /**
     * Calculate task completion rate for an employee
     */
    private function calculateTaskCompletionRate(Employee $employee, array $filters): int
    {
        // Since you don't have a tasks relationship, return a default value
        // You can implement actual task logic when you add tasks to your system
        return 80; // Default task completion rate
    }

    /**
     * Calculate punctuality rate for an employee
     */
    private function calculatePunctualityRate(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances()->where('status', 'present');
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }

        $totalAttendance = $attendanceQuery->count();
        
        // Count on-time attendance (assuming 9:15 AM as cutoff for on-time)
        $onTimeAttendance = $attendanceQuery->whereTime('clock_in', '<=', '09:15:00')->count();

        return $totalAttendance > 0 ? round(($onTimeAttendance / $totalAttendance) * 100) : 100;
    }
   
     /**
     * Generate comprehensive attendance report with filters
     */
    public function generateAttendanceReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'employee_id' => 'sometimes|exists:employees,id',
            'report_type' => 'sometimes|in:summary,detailed,department'
        ]);

        try {
            $reportData = $this->reportService->generateAttendanceReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'Attendance report generated successfully',
                'report' => $reportData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating attendance report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive leave report with filters
     */
    public function generateLeaveReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'employee_id' => 'sometimes|exists:employees,id',
            'leave_type' => 'sometimes|in:all,vacation,sick,personal,maternity,paternity',
            'status' => 'sometimes|in:all,pending,approved,rejected'
        ]);

        try {
            $reportData = $this->reportService->generateLeaveReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'Leave report generated successfully',
                'report' => $reportData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating leave report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate leave report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive payroll report with filters
     */
    public function generatePayrollReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'status' => 'sometimes|in:all,draft,processing,completed,failed',
            'pay_period' => 'sometimes|in:monthly,biweekly,weekly'
        ]);

        try {
            $reportData = $this->reportService->generatePayrollReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'Payroll report generated successfully',
                'report' => $reportData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating payroll report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payroll report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate organization overview report
     */
    public function generateOrganizationReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        try {
            $startDate = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

            // Get all reports data
            $attendanceReport = $this->reportService->generateAttendanceReport($filters);
            $leaveReport = $this->reportService->generateLeaveReport($filters);
            $payrollReport = $this->reportService->generatePayrollReport($filters);

            // Calculate organization statistics
            $totalEmployees = Employee::count();
            $totalDepartments = \App\Models\Department::count();
            $presentToday = Attendance::whereDate('date', now()->format('Y-m-d'))
                ->where('status', 'present')
                ->count();
            $pendingLeaves = Leave::where('status', 'pending')->count();

            $organizationReport = [
                'period_start' => $startDate,
                'period_end' => $endDate,
                'report_type' => 'organization_overview',
                'organization_stats' => [
                    'total_employees' => $totalEmployees,
                    'total_departments' => $totalDepartments,
                    'present_today' => $presentToday,
                    'pending_leaves' => $pendingLeaves,
                    'attendance_rate' => $attendanceReport['attendance_rate'] ?? 0,
                    'leave_approval_rate' => $leaveReport['approval_rate'] ?? 0,
                ],
                'attendance_summary' => $attendanceReport['attendance_summary'] ?? [],
                'leave_summary' => [
                    'total_requests' => $leaveReport['total_leave_requests'] ?? 0,
                    'approved' => $leaveReport['status_breakdown']['approved'] ?? 0,
                    'pending' => $leaveReport['status_breakdown']['pending'] ?? 0,
                ],
                'payroll_summary' => [
                    'total_processed' => $payrollReport['processed_employees'] ?? 0,
                    'total_payroll' => $payrollReport['total_net_salary'] ?? 0,
                    'average_salary' => $payrollReport['average_net_salary'] ?? 0,
                ],
                'department_performance' => $this->getDepartmentPerformance($filters),
                'generated_at' => now()->toDateTimeString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Organization report generated successfully',
                'report' => $organizationReport
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating organization report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate organization report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department performance metrics
     */
    private function getDepartmentPerformance(array $filters): array
    {
        $departments = \App\Models\Department::with(['employees', 'employees.attendances', 'employees.leaves'])->get();

        return $departments->map(function ($department) use ($filters) {
            $employees = $department->employees;
            $employeeCount = $employees->count();

            if ($employeeCount === 0) {
                return [
                    'department' => $department->name,
                    'employee_count' => 0,
                    'attendance_rate' => 0,
                    'productivity_score' => 0,
                    'leave_utilization' => 0,
                ];
            }

            // Calculate attendance rate
            $attendanceQuery = Attendance::whereIn('employee_id', $employees->pluck('id'));
            if (isset($filters['start_date'])) {
                $attendanceQuery->where('date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $attendanceQuery->where('date', '<=', $filters['end_date']);
            }

            $totalAttendance = $attendanceQuery->count();
            $presentAttendance = $attendanceQuery->where('status', 'present')->count();
            $attendanceRate = $totalAttendance > 0 ? ($presentAttendance / $totalAttendance) * 100 : 0;

            // Calculate leave utilization
            $leaveQuery = Leave::whereIn('employee_id', $employees->pluck('id'))
                ->where('status', 'approved');
            
            if (isset($filters['start_date'])) {
                $leaveQuery->where('start_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $leaveQuery->where('end_date', '<=', $filters['end_date']);
            }

            $totalLeaveDays = $leaveQuery->sum('total_days');
            $maxPossibleLeaveDays = $employeeCount * 30; // Assuming 30 working days per month
            $leaveUtilization = $maxPossibleLeaveDays > 0 ? ($totalLeaveDays / $maxPossibleLeaveDays) * 100 : 0;

            return [
                'department' => $department->name,
                'employee_count' => $employeeCount,
                'attendance_rate' => round($attendanceRate, 2),
                'productivity_score' => round($attendanceRate * 0.8), // Simplified productivity score
                'leave_utilization' => round($leaveUtilization, 2),
                'active_employees' => $employeeCount, // All employees considered active in current structure
            ];
        })->toArray();
    }

    /**
     * Download generated report
     */
    public function downloadReport(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:pdf,csv,excel',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $filters = $request->only(['start_date', 'end_date', 'department', 'status']);

        try {
            switch ($type) {
                case 'attendance':
                    $data = $this->reportService->generateAttendanceReport($filters);
                    $filename = "attendance-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                    break;
                    
                case 'leave':
                    $data = $this->reportService->generateLeaveReport($filters);
                    $filename = "leave-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                    break;
                    
                case 'payroll':
                    $data = $this->reportService->generatePayrollReport($filters);
                    $filename = "payroll-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                    break;
                    
                case 'organization':
                    $data = $this->generateOrganizationReport(new Request($filters))->getData()->report;
                    $filename = "organization-report-{$filters['start_date']}-to-{$filters['end_date']}." . $request->format;
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 422);
            }

            if ($request->format === 'pdf') {
                return $this->reportService->exportToPdf("reports.{$type}", $data, $filename);
            } else {
                return $this->reportService->exportToCsv($data, $filename);
            }

        } catch (\Exception $e) {
            \Log::error('Error downloading report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
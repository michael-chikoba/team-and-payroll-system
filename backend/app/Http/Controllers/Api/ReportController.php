<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportGeneratorService;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Models\Payslip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // ADD THIS IMPORT
use App\Models\Payroll;

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


     public function getAdminStats(): JsonResponse
    {
        try {
            $totalEmployees = Employee::count();
            
            $today = Carbon::today()->toDateString();
            $presentToday = Attendance::whereDate('date', $today)
                ->where('status', 'present')
                ->count();
            
            $pendingLeaves = Leave::where('status', 'pending')->count();
            
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            
            $totalAttendanceDays = Attendance::whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', 'present')
                ->count();
                
            $workingDays = $currentMonthStart->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $currentMonthEnd);
            
            $avgAttendance = $workingDays > 0 ? round(($totalAttendanceDays / ($totalEmployees * $workingDays)) * 100, 2) : 0;

            return response()->json([
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves,
                'avg_attendance' => $avgAttendance,
                'month' => Carbon::now()->format('F Y')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching admin stats: ' . $e->getMessage());
            return response()->json([
                'total_employees' => 0,
                'present_today' => 0,
                'pending_leaves' => 0,
                'avg_attendance' => 0,
                'month' => Carbon::now()->format('F Y')
            ]);
        }
    }


    public function getReportParams($type): JsonResponse
    {
        $defaults = [
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];

        switch ($type) {
            case 'attendance':
                $defaults['department'] = '';
                $defaults['report_type'] = 'summary';
                break;
            case 'leave':
                $defaults['leave_type'] = '';
                $defaults['status'] = '';
                break;
            case 'payroll':
                $defaults['department'] = '';
                $defaults['status'] = 'all';
                break;
        }

        return response()->json($defaults);
    }

    /**
     * Get generated reports list
     */
    public function getGeneratedReports(): JsonResponse
    {
        // For now, return empty array or mock data
        // In production, you'd query a reports table
        return response()->json([]);
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
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'report_type' => 'sometimes|in:summary,detailed,daily'
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            // Base query for attendance
            $query = Attendance::with(['employee.user'])
                ->whereBetween('date', [$startDate, $endDate]);

            // Apply department filter ONLY if provided and not empty
            // Empty string means "All Departments"
            if (!empty($validated['department'])) {
                $query->whereHas('employee', function ($q) use ($validated) {
                    $q->where('department', $validated['department']);
                });
            }

            $attendanceData = $query->get();
            
            // Calculate summary statistics
            // For employee count, apply the same department filter
            $totalEmployees = Employee::when(!empty($validated['department']), function ($q) use ($validated) {
                $q->where('department', $validated['department']);
            })->count();

            $workingDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $endDate);

            $presentDays = $attendanceData->where('status', 'present')->count();
            $absentDays = $attendanceData->where('status', 'absent')->count();
            $lateDays = $attendanceData->where('status', 'late')->count();

            $attendanceRate = $workingDays > 0 ? round(($presentDays / ($totalEmployees * $workingDays)) * 100, 2) : 0;

            // Determine department display name
            $departmentName = !empty($validated['department']) 
                ? $validated['department'] 
                : 'All Departments';

            $reportData = [
                'department' => $departmentName,
                'total_employees' => $totalEmployees,
                'working_days' => $workingDays,
                'attendance_summary' => [
                    'present' => $presentDays,
                    'absent' => $absentDays,
                    'late' => $lateDays,
                ],
                'attendance_rate' => $attendanceRate,
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'generated_at' => now()->toDateTimeString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Attendance report generated successfully',
                'data' => $reportData,
                'type' => 'attendance'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating attendance report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive leave report with filters
     */
       public function generateLeaveReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'sometimes|string',
            'status' => 'sometimes|in:pending,approved,rejected,all'
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            // Base query for leaves
            $query = Leave::with(['employee.user'])
                ->whereBetween('start_date', [$startDate, $endDate]);

            // Apply filters - only if not empty
            if (!empty($validated['leave_type'])) {
                $query->where('leave_type', $validated['leave_type']);
            }

            if (!empty($validated['status']) && $validated['status'] !== 'all') {
                $query->where('status', $validated['status']);
            }

            $leaveData = $query->get();

            // Calculate statistics
            $totalLeaveRequests = $leaveData->count();
            $statusBreakdown = $leaveData->groupBy('status')->map->count();
            
            $approvedCount = $statusBreakdown->get('approved', 0);
            $approvalRate = $totalLeaveRequests > 0 ? round(($approvedCount / $totalLeaveRequests) * 100, 2) : 0;

            $reportData = [
                'total_leave_requests' => $totalLeaveRequests,
                'status_breakdown' => $statusBreakdown,
                'approval_rate' => $approvalRate,
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'generated_at' => now()->toDateTimeString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Leave report generated successfully',
                'data' => $reportData,
                'type' => 'leave'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating leave report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate leave report: ' . $e->getMessage()
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
            $attendanceReport = $this->generateAttendanceReport(new Request($filters))->getData(true);
            $leaveReport = $this->generateLeaveReport(new Request($filters))->getData(true);
            $payrollReport = $this->generatePayrollReport(new Request($filters))->getData(true);
            
            // Calculate organization statistics
            $totalEmployees = Employee::count();
            $totalDepartments = Employee::distinct('department')->count('department');
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
                    'attendance_rate' => $attendanceReport['data']['attendance_rate'] ?? 0,
                    'leave_approval_rate' => $leaveReport['data']['approval_rate'] ?? 0,
                ],
                'attendance_summary' => $attendanceReport['data']['attendance_summary'] ?? [],
                'leave_summary' => [
                    'total_requests' => $leaveReport['data']['total_leave_requests'] ?? 0,
                    'approved' => $leaveReport['data']['status_breakdown']['approved'] ?? 0,
                    'pending' => $leaveReport['data']['status_breakdown']['pending'] ?? 0,
                ],
                'payroll_summary' => [
                    'total_processed' => $payrollReport['data']['processed_employees'] ?? 0,
                    'total_payroll' => $payrollReport['data']['total_net_salary'] ?? 0,
                    'average_salary' => $payrollReport['data']['average_net_salary'] ?? 0,
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
            Log::error('Error generating organization report: ' . $e->getMessage());
            
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
        $departments = Employee::select('department')->distinct()->get()->pluck('department');
        
        return $departments->map(function ($department) use ($filters) {
            if (empty($department)) return null;
            
            $employees = Employee::where('department', $department)->get();
            $employeeCount = $employees->count();
            if ($employeeCount === 0) {
                return [
                    'department' => $department,
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
                'department' => $department,
                'employee_count' => $employeeCount,
                'attendance_rate' => round($attendanceRate, 2),
                'productivity_score' => round($attendanceRate * 0.8), // Simplified productivity score
                'leave_utilization' => round($leaveUtilization, 2),
                'active_employees' => $employeeCount,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Generate comprehensive payroll report with filters
     */
        public function generatePayrollReport(Request $request): JsonResponse
    {
        // Custom validation to allow empty string for department
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'nullable|string', // Changed to nullable
            'status' => 'sometimes|in:all,paid,pending'
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            // Clean up department parameter - treat empty string as null
            $department = !empty($validated['department']) ? $validated['department'] : null;
            
            Log::info('Generating payroll report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'department' => $department ?? 'All Departments',
                'status' => $validated['status'] ?? 'all'
            ]);
            
            // Find payrolls in the date range
            $query = Payroll::with(['payslips.employee.user'])
                ->whereBetween('start_date', [$startDate, $endDate]);

            $payrolls = $query->get();

            $totalNetSalary = 0;
            $totalGrossSalary = 0;
            $totalTaxAmount = 0;
            $totalPaye = 0;
            $totalNapsa = 0;
            $totalNhima = 0;
            $processedEmployees = 0;
            $payslipDetails = [];
            $departmentBreakdown = [];

            foreach ($payrolls as $payroll) {
                foreach ($payroll->payslips as $payslip) {
                    // Apply department filter ONLY if a specific department is selected
                    if ($department !== null && $payslip->employee->department !== $department) {
                        continue;
                    }

                    // Apply status filter
                    if (!empty($validated['status']) && $validated['status'] !== 'all' &&
                        $payslip->status !== $validated['status']) {
                        continue;
                    }

                    $grossSalary = $payslip->gross_salary ?? 0;
                    $netPay = $payslip->net_pay ?? 0;
                    $paye = $payslip->paye ?? 0;
                    $napsa = $payslip->napsa ?? 0;
                    $nhima = $payslip->nhima ?? 0;

                    $totalGrossSalary += $grossSalary;
                    $totalNetSalary += $netPay;
                    $totalPaye += $paye;
                    $totalNapsa += $napsa;
                    $totalNhima += $nhima;
                    $totalTaxAmount += ($paye + $napsa + $nhima);
                    $processedEmployees++;

                    // Track department breakdown for "All Departments" view
                    $empDepartment = $payslip->employee->department ?? 'Unassigned';
                    if (!isset($departmentBreakdown[$empDepartment])) {
                        $departmentBreakdown[$empDepartment] = [
                            'employee_count' => 0,
                            'total_net_salary' => 0,
                            'total_gross_salary' => 0,
                        ];
                    }
                    $departmentBreakdown[$empDepartment]['employee_count']++;
                    $departmentBreakdown[$empDepartment]['total_net_salary'] += $netPay;
                    $departmentBreakdown[$empDepartment]['total_gross_salary'] += $grossSalary;

                    $payslipDetails[] = [
                        'employee_id' => $payslip->employee->id,
                        'employee_name' => $payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name,
                        'department' => $empDepartment,
                        'gross_salary' => number_format($grossSalary, 2),
                        'deductions' => number_format($payslip->total_deductions ?? 0, 2),
                        'net_salary' => number_format($netPay, 2),
                        'paye' => number_format($paye, 2),
                        'napsa' => number_format($napsa, 2),
                        'nhima' => number_format($nhima, 2),
                        'tax_amount' => number_format($paye + $napsa + $nhima, 2),
                        'pay_period' => $payroll->payroll_period,
                        'status' => $payslip->status ?? 'pending',
                    ];
                }
            }

            $averageNetSalary = $processedEmployees > 0 ? $totalNetSalary / $processedEmployees : 0;
            $averageGrossSalary = $processedEmployees > 0 ? $totalGrossSalary / $processedEmployees : 0;

            // Determine department display name
            $departmentName = $department ?? 'All Departments';

            $reportData = [
                'department' => $departmentName,
                'total_gross_salary' => number_format($totalGrossSalary, 2),
                'total_net_salary' => number_format($totalNetSalary, 2),
                'total_tax_amount' => number_format($totalTaxAmount, 2),
                'total_paye' => number_format($totalPaye, 2),
                'total_napsa' => number_format($totalNapsa, 2),
                'total_nhima' => number_format($totalNhima, 2),
                'processed_employees' => $processedEmployees,
                'average_gross_salary' => number_format($averageGrossSalary, 2),
                'average_net_salary' => number_format($averageNetSalary, 2),
                'payslip_details' => $payslipDetails,
                'department_breakdown' => $department === null ? $departmentBreakdown : null, // Only include for "All Departments"
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'generated_at' => now()->toDateTimeString(),
            ];

            Log::info('Payroll report generated successfully', [
                'processed_employees' => $processedEmployees,
                'total_net_salary' => $totalNetSalary
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll report generated successfully',
                'data' => $reportData,
                'type' => 'payroll'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating payroll report: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payroll report: ' . $e->getMessage()
            ], 500);
        }
    }
 public function exportReport(Request $request, string $type)
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'status' => 'sometimes|in:all,paid,pending,approved,rejected',
        ]);

        try {
            $filters = $validated;
            $format = $validated['format'];
            
            // Generate appropriate report data
            switch ($type) {
                case 'payroll':
                    $reportData = $this->reportService->generatePayrollReport($filters);
                    $filename = "payroll_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.payroll';
                    break;
                  
                case 'attendance':
                    $reportData = $this->reportService->generateAttendanceReport($filters);
                    $filename = "attendance_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.attendance';
                    break;
                  
                case 'leave':
                    $reportData = $this->reportService->generateLeaveReport($filters);
                    $filename = "leave_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.leave';
                    break;
                  
                case 'productivity':
                    $reportData = $this->reportService->generateProductivityReport($filters);
                    $filename = "productivity_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.productivity';
                    break;
                  
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 422);
            }

            Log::info("Generating {$type} report", [
                'format' => $format,
                'filters' => $filters,
                'data_count' => count($reportData['data'] ?? [])
            ]);

            if ($format === 'pdf') {
                return $this->reportService->exportToPdf($view, $reportData, $filename);
            } else {
                return $this->reportService->exportToCsv($reportData['data'] ?? [], $filename);
            }

        } catch (\Exception $e) {
            Log::error("Export {$type} report error: " . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadReport(Request $request, $type)
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'status' => 'sometimes|in:all,paid,pending,approved,rejected',
        ]);

        try {
            return $this->exportReport($request, $type);
            
        } catch (\Exception $e) {
            Log::error('Error downloading report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get payroll report data without JSON response wrapper
     */
    private function generatePayrollReportData(array $filters): array
    {
        // Reuse the logic from generatePayrollReport
        $query = Payslip::with('employee');
        if (isset($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        $query->where('pay_period_start', '>=', $filters['start_date'])
              ->where('pay_period_end', '<=', $filters['end_date']);
        $payslips = $query->get();
        $processedEmployees = $payslips->count();
        $totalNetSalary = $payslips->sum('net_pay');
        $totalTaxAmount = $payslips->sum('paye') + $payslips->sum('napsa') + $payslips->sum('nhima');
        $averageNetSalary = $processedEmployees > 0 ? round($totalNetSalary / $processedEmployees, 2) : 0;
        return [
            'period_start' => $filters['start_date'],
            'period_end' => $filters['end_date'],
            'processed_employees' => $processedEmployees,
            'total_net_salary' => $totalNetSalary,
            'average_net_salary' => $averageNetSalary,
            'total_tax_amount' => $totalTaxAmount,
            'payslip_details' => $payslips->map(function ($payslip) {
                $startDate = Carbon::parse($payslip->pay_period_start)->format('M d, Y');
                $endDate = Carbon::parse($payslip->pay_period_end)->format('M d, Y');
                return [
                    'employee_id' => $payslip->employee_id,
                    'employee_name' => $payslip->employee->user ? ($payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name) : 'N/A',
                    'gross_salary' => $payslip->gross_salary ?? 0,
                    'deductions' => $payslip->total_deductions ?? 0,
                    'net_salary' => $payslip->net_pay ?? 0,
                    'tax_amount' => ($payslip->paye ?? 0) + ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0),
                    'pay_period' => $startDate . ' to ' . $endDate,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate Payroll CSV in the exact template format from the shared document
     * Data sourced from payslips within the filter period
     */
    private function generatePayrollCsvTemplate(array $filters): string
    {
        // Header rows from the template (hardcoded as per shared document; customize IDs/dates as needed)
        $csv = "BInSol - U ver 1.00,,,,,,,, \n";
        $csv .= now()->format('m/d/Y') . ",,,,,,,,, \n"; // Dynamic date instead of 7/2/2015
        $csv .= "62000031451,1.23457E+11,,,,,,, \n"; // Company/Batch IDs (customize from config)
        $csv .= "RECIPIENT NAME,RECIPIENT ACCOUNT,RECIPIENT ACCOUNT TYPE,BRANCHCODE,AMOUNT,OWN REFERENCE,RECIPIENT REFERENCE,EMAIL 1 NOTIFY,EMAIL 1 ADDRESS\n";
        // Fetch payslips for the period (source data from payslips)
        $query = Payslip::with('employee');
        if (isset($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        $query->where('pay_period_start', '>=', $filters['start_date'])
              ->where('pay_period_end', '<=', $filters['end_date']);
        $payslips = $query->get();
        // Map payslips to CSV rows
        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $row = [
                '"' . ($employee->user ? ($employee->user->first_name . ' ' . $employee->user->last_name) : 'N/A') . '"', // RECIPIENT NAME (quoted for commas)
                $employee->bank_account ?? '', // RECIPIENT ACCOUNT (assume field exists on Employee; add if needed)
                $employee->account_type ?? 'Checking', // RECIPIENT ACCOUNT TYPE (assume field; default)
                $employee->branch_code ?? '', // BRANCHCODE (assume field; add if needed)
                number_format($payslip->net_pay ?? 0, 2, '.', ''), // AMOUNT (net pay, formatted)
                'Payroll-' . ($payslip->payroll_id ?? $payslip->id), // OWN REFERENCE (e.g., payroll ID)
                $employee->id, // RECIPIENT REFERENCE (employee ID)
                'Y', // EMAIL 1 NOTIFY (flag for email notification)
                '"' . ($employee->user?->email ?? '') . '"', // EMAIL 1 ADDRESS (quoted)
            ];
            $csv .= implode(',', $row) . "\n";
        }
        return $csv;
    }
}
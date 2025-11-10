<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Employee;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportGeneratorService
{
    public function generatePayrollReport(array $filters = [])
    {
        $query = Payroll::with('payslips.employee.user');

        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $payrolls = $query->orderBy('created_at', 'desc')->get();

        $reportData = [
            'payrolls' => $payrolls,
            'filters' => $filters,
            'generated_at' => now(),
            'total_gross' => $payrolls->sum('total_gross'),
            'total_net' => $payrolls->sum('total_net'),
            'total_employees' => $payrolls->sum('employee_count'),
        ];

        return $reportData;
    }

    public function generateAttendanceReport(array $filters = [])
    {
        $query = Attendance::with('employee.user');

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'attendance_rate' => $attendances->count() > 0 ? 
                ($attendances->where('status', 'present')->count() / $attendances->count()) * 100 : 0,
        ];

        return [
            'attendances' => $attendances,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    public function generateLeaveReport(array $filters = [])
    {
        $query = Leave::with(['employee.user', 'manager']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();

        $summary = [
            'total_leaves' => $leaves->count(),
            'approved_leaves' => $leaves->where('status', 'approved')->count(),
            'pending_leaves' => $leaves->where('status', 'pending')->count(),
            'rejected_leaves' => $leaves->where('status', 'rejected')->count(),
            'total_days' => $leaves->sum('total_days'),
            'approval_rate' => $leaves->whereIn('status', ['approved', 'rejected'])->count() > 0 ?
                ($leaves->where('status', 'approved')->count() / $leaves->whereIn('status', ['approved', 'rejected'])->count()) * 100 : 0,
        ];

        return [
            'leaves' => $leaves,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    public function exportToPdf(string $view, array $data, string $filename)
    {
        $pdf = PDF::loadView($view, $data);
        return $pdf->download($filename);
    }
public function generateProductivityReport(array $filters = []): array
{
    $query = Employee::with(['user', 'attendances', 'leaves']);

    if (isset($filters['employee_id'])) {
        $query->where('id', $filters['employee_id']);
    }

    if (isset($filters['department_id'])) {
        $query->where('department_id', $filters['department_id']);
    }

    $employees = $query->get();

    // Calculate productivity metrics for each employee
    $productivityData = $employees->map(function ($employee) use ($filters) {
        // Filter attendances by date range if provided
        $attendances = $employee->attendances;
        
        if (isset($filters['start_date'])) {
            $attendances = $attendances->where('date', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $attendances = $attendances->where('date', '<=', $filters['end_date']);
        }

        // Calculate metrics
        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $totalHours = $attendances->sum('total_hours');
        
        $attendanceRate = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
        
        // Calculate leaves
        $leaves = $employee->leaves;
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $leaves = $leaves->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }
        
        $approvedLeaves = $leaves->where('status', 'approved')->sum('total_days');
        
        // Calculate productivity score (0-100)
        $productivityScore = $this->calculateProductivityScore(
            $attendanceRate,
            $lateDays,
            $totalDays
        );

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->name ?? 'N/A',
            'email' => $employee->user->email ?? 'N/A',
            'total_working_days' => $totalDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'total_hours_worked' => round($totalHours, 2),
            'approved_leave_days' => $approvedLeaves,
            'attendance_rate' => round($attendanceRate, 2),
            'productivity_score' => round($productivityScore, 2),
        ];
    });

    // Calculate summary statistics
    $summary = [
        'total_employees' => $employees->count(),
        'average_attendance_rate' => round($productivityData->avg('attendance_rate'), 2),
        'average_productivity_score' => round($productivityData->avg('productivity_score'), 2),
        'total_hours_worked' => round($productivityData->sum('total_hours_worked'), 2),
        'average_hours_per_employee' => $employees->count() > 0 ? 
            round($productivityData->sum('total_hours_worked') / $employees->count(), 2) : 0,
    ];

    return [
        'data' => $productivityData,
        'summary' => $summary,
        'filters' => $filters,
        'generated_at' => now(),
    ];
}

private function calculateProductivityScore(float $attendanceRate, int $lateDays, int $totalDays): float
{
    // Base score from attendance rate (0-70 points)
    $attendanceScore = ($attendanceRate / 100) * 70;
    
    // Deduct points for late days (up to 20 points)
    $latenessPenalty = $totalDays > 0 ? ($lateDays / $totalDays) * 20 : 0;
    
    // Consistency bonus (up to 30 points) - full bonus if attendance rate is above 95%
    $consistencyBonus = $attendanceRate >= 95 ? 30 : ($attendanceRate / 95) * 30;
    
    $score = $attendanceScore - $latenessPenalty + $consistencyBonus;
    
    // Ensure score is between 0 and 100
    return max(0, min(100, $score));
}
    public function exportToCsv(array $data, string $filename)
    {
        $handle = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
        }
        
        // Add data
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        return response()->streamDownload(function () use ($handle) {
            echo $handle;
        }, $filename);
    }
}
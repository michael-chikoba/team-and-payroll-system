<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Employee;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportGeneratorService
{
    /**
     * Generate payroll report data with other deductions
     */
    public function generatePayrollReport(array $filters = [])
    {
        // Query payslips directly
        $query = Payslip::with(['employee.user']);

        if (isset($filters['start_date'])) {
            $query->where('pay_period_start', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('pay_period_end', '<=', $filters['end_date']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Only filter by department if a specific department is provided (not null or empty)
        if (isset($filters['department']) && !empty($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }

        $payslips = $query->orderBy('pay_period_start', 'desc')->get();

        // Calculate totals including other deductions
        $totalGrossSalary = $payslips->sum('gross_salary');
        $totalNetSalary = $payslips->sum('net_pay');
        $totalPaye = $payslips->sum('paye');
        $totalNapsa = $payslips->sum('napsa');
        $totalNhima = $payslips->sum('nhima');
        $totalOtherDeductions = $payslips->sum('other_deductions');
        $totalAllDeductions = $payslips->sum('total_deductions');
        $totalTaxAmount = $totalPaye + $totalNapsa + $totalNhima;
        $processedEmployees = $payslips->count();
        $averageNetSalary = $processedEmployees > 0 ? $totalNetSalary / $processedEmployees : 0;
        $averageGrossSalary = $processedEmployees > 0 ? $totalGrossSalary / $processedEmployees : 0;

        // Calculate department breakdown if "All Departments" is selected
        $departmentBreakdown = [];
        if (!isset($filters['department']) || empty($filters['department'])) {
            foreach ($payslips as $payslip) {
                $dept = $payslip->employee->department ?? 'Unassigned';
                
                if (!isset($departmentBreakdown[$dept])) {
                    $departmentBreakdown[$dept] = [
                        'employee_count' => 0,
                        'total_gross_salary' => 0,
                        'total_net_salary' => 0,
                        'total_tax' => 0,
                        'total_other_deductions' => 0,
                    ];
                }
                
                $departmentBreakdown[$dept]['employee_count']++;
                $departmentBreakdown[$dept]['total_gross_salary'] += $payslip->gross_salary ?? 0;
                $departmentBreakdown[$dept]['total_net_salary'] += $payslip->net_pay ?? 0;
                $departmentBreakdown[$dept]['total_tax'] += ($payslip->paye ?? 0) + ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0);
                $departmentBreakdown[$dept]['total_other_deductions'] += $payslip->other_deductions ?? 0;
            }
        }

        // Format payslip details with other deductions
        $payslipDetails = $payslips->map(function ($payslip) {
            return [
                'employee_id' => $payslip->employee_id,
                'employee_name' => $payslip->employee->user 
                    ? ($payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name) 
                    : 'N/A',
                'department' => $payslip->employee->department ?? 'Unassigned',
                'gross_salary' => $payslip->gross_salary ?? 0,
                'deductions' => $payslip->total_deductions ?? 0,
                'net_salary' => $payslip->net_pay ?? 0,
                'paye' => $payslip->paye ?? 0,
                'napsa' => $payslip->napsa ?? 0,
                'nhima' => $payslip->nhima ?? 0,
                'other_deductions' => $payslip->other_deductions ?? 0,
                'tax_amount' => ($payslip->paye ?? 0) + ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0),
                'pay_period' => Carbon::parse($payslip->pay_period_start)->format('M d, Y') 
                    . ' - ' 
                    . Carbon::parse($payslip->pay_period_end)->format('M d, Y'),
                'payment_date' => $payslip->payment_date 
                    ? Carbon::parse($payslip->payment_date)->format('M d, Y') 
                    : 'N/A',
                'status' => $payslip->status ?? 'N/A',
            ];
        })->toArray();

        return [
            'data' => $payslipDetails,
            'summary' => [
                'period_start' => $filters['start_date'] ?? 'N/A',
                'period_end' => $filters['end_date'] ?? 'N/A',
                'department' => $filters['department'] ?? 'All Departments',
                'processed_employees' => $processedEmployees,
                'total_gross_salary' => $totalGrossSalary,
                'total_net_salary' => $totalNetSalary,
                'average_gross_salary' => $averageGrossSalary,
                'average_net_salary' => $averageNetSalary,
                'total_tax_amount' => $totalTaxAmount,
                'total_paye' => $totalPaye,
                'total_napsa' => $totalNapsa,
                'total_nhima' => $totalNhima,
                'total_other_deductions' => $totalOtherDeductions,
                'total_all_deductions' => $totalAllDeductions,
                'department_breakdown' => !empty($departmentBreakdown) ? $departmentBreakdown : null,
            ],
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate attendance report
     */
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

        if (isset($filters['department'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
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

        // Format attendance data for export
        $attendanceData = $attendances->map(function ($attendance) {
            return [
                'employee_name' => $attendance->employee->user 
                    ? ($attendance->employee->user->first_name . ' ' . $attendance->employee->user->last_name)
                    : 'N/A',
                'date' => Carbon::parse($attendance->date)->format('M d, Y'),
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i A') : 'N/A',
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i A') : 'N/A',
                'total_hours' => $attendance->total_hours ?? 0,
                'status' => $attendance->status ?? 'N/A',
            ];
        })->toArray();

        return [
            'data' => $attendanceData,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Generate leave report
     */
    public function generateLeaveReport(array $filters = [])
    {
        $query = Leave::with(['employee.user']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
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

        // Format leave data for export
        $leaveData = $leaves->map(function ($leave) {
            return [
                'employee_name' => $leave->employee->user 
                    ? ($leave->employee->user->first_name . ' ' . $leave->employee->user->last_name)
                    : 'N/A',
                'leave_type' => $leave->type ?? 'N/A',
                'start_date' => Carbon::parse($leave->start_date)->format('M d, Y'),
                'end_date' => Carbon::parse($leave->end_date)->format('M d, Y'),
                'total_days' => $leave->total_days ?? 0,
                'status' => $leave->status ?? 'N/A',
                'reason' => $leave->reason ?? 'N/A',
            ];
        })->toArray();

        return [
            'data' => $leaveData,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportToPdf(string $view, array $data, string $filename)
    {
        try {
            Log::info('Generating PDF', [
                'view' => $view,
                'filename' => $filename,
                'data_keys' => array_keys($data)
            ]);

            // Prepare data for the view - FIXED: Pass the complete data structure
            $reportData = $this->prepareDataForPdf($data);

            // Generate PDF
            $pdf = PDF::loadView($view, ['report' => $reportData])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'defaultFont' => 'sans-serif'
                ]);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            throw $e;
        }
    }

    /**
     * Prepare data structure for PDF views - FIXED VERSION
     */
    private function prepareDataForPdf(array $data): array
    {
        Log::info('Preparing data for PDF', [
            'data_structure' => array_keys($data),
            'has_summary' => isset($data['summary']),
            'has_data' => isset($data['data'])
        ]);

        // If data already has the correct structure from ReportController, return it directly
        if (isset($data['total_net_salary']) && isset($data['payslip_details'])) {
            Log::info('Using direct data structure from ReportController');
            return $data;
        }

        // If data has summary and data sections (from ReportGeneratorService)
        if (isset($data['summary']) && isset($data['data'])) {
            Log::info('Using summary/data structure from ReportGeneratorService', [
                'summary_keys' => array_keys($data['summary'])
            ]);
            
            return [
                'period_start' => $data['summary']['period_start'] ?? $data['filters']['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
                'period_end' => $data['summary']['period_end'] ?? $data['filters']['end_date'] ?? now()->format('Y-m-d'),
                'processed_employees' => $data['summary']['processed_employees'] ?? 0,
                'total_gross_salary' => $data['summary']['total_gross_salary'] ?? 0,
                'total_net_salary' => $data['summary']['total_net_salary'] ?? 0,
                'average_net_salary' => $data['summary']['average_net_salary'] ?? 0,
                'total_tax_amount' => $data['summary']['total_tax_amount'] ?? 0,
                'total_paye' => $data['summary']['total_paye'] ?? 0,
                'total_napsa' => $data['summary']['total_napsa'] ?? 0,
                'total_nhima' => $data['summary']['total_nhima'] ?? 0,
                'total_other_deductions' => $data['summary']['total_other_deductions'] ?? 0,
                'total_all_deductions' => $data['summary']['total_all_deductions'] ?? 0,
                'payslip_details' => $data['data'] ?? [],
                'generated_at' => $data['generated_at'] ?? now(),
            ];
        }

        Log::warning('Unknown data structure for PDF preparation', [
            'data_keys' => array_keys($data)
        ]);

        return $data;
    }

    /**
     * Export report to CSV with proper formatting
     */
    public function exportToCsv(array $data, string $filename)
    {
        try {
            Log::info('Generating CSV', [
                'filename' => $filename,
                'data_count' => count($data)
            ]);

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8 encoding (helps with Excel)
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Add headers if data exists
                if (!empty($data)) {
                    fputcsv($file, array_keys($data[0]));
                    
                    // Add data rows
                    foreach ($data as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    // Add a message if no data
                    fputcsv($file, ['No data available for the selected period']);
                }
                
                fclose($file);
            };

            return response()->streamDownload($callback, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('CSV Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate productivity report
     */
    public function generateProductivityReport(array $filters = []): array
    {
        $query = Employee::with(['user', 'attendances', 'leaves']);

        if (isset($filters['employee_id'])) {
            $query->where('id', $filters['employee_id']);
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
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
                'employee_name' => $employee->user->first_name . ' ' . $employee->user->last_name ?? 'N/A',
                'email' => $employee->user->email ?? 'N/A',
                'department' => $employee->department ?? 'N/A',
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
            'data' => $productivityData->toArray(),
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Calculate productivity score
     */
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
}
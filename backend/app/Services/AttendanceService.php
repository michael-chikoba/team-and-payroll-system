<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * Clock in an employee
     */
    public function clockIn(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();
        $cutoffTime = $now->copy()->setTime(16, 0); // 4:00 PM CAT

        // First, handle any unclosed attendance records
        $this->handleUncloseAttendance($employee, $now, $today, $cutoffTime);

        // Check if already clocked in today (after cleanup)
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($todayAttendance) {
            if ($todayAttendance->clock_out) {
                // Already completed for today - allow new clock-in
                Log::info('Employee already completed work today, creating new record', [
                    'employee_id' => $employee->id,
                    'date' => $today
                ]);
            } else {
                // Still clocked in
                throw new \Exception('Already clocked in today at ' . $todayAttendance->clock_in);
            }
        }

        // Determine status: late if after 8:30 AM
        $expectedIn = $now->copy()->setTime(8, 30);
        $status = ($now->greaterThan($expectedIn)) ? 'late' : 'present';

        // Create new attendance record
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'clock_in' => $now->toTimeString(),
            'status' => $status,
            'break_minutes' => 0,
        ]);

        Log::info('Employee clocked in successfully', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'clock_in' => $attendance->clock_in,
            'status' => $status
        ]);

        return $attendance;
    }

    /**
     * Clock out an employee
     */
    public function clockOut(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Find today's unclosed attendance
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            throw new \Exception('No active clock-in found for today. Please clock in first.');
        }

        $attendance->clock_out = $now->toTimeString();
        $attendance->total_hours = $attendance->calculateTotalHours();
        $attendance->save();

        Log::info('Employee clocked out successfully', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'clock_out' => $attendance->clock_out,
            'total_hours' => $attendance->total_hours
        ]);

        return $attendance;
    }

    /**
     * Get today's attendance status
     */
    public function getTodayStatus(Employee $employee): string
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();
        $cutoffTime = $now->copy()->setTime(16, 0); // 4:00 PM CAT

        // First, handle any unclosed attendance
        $this->handleUncloseAttendance($employee, $now, $today, $cutoffTime);

        // Now get today's attendance
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return 'absent';
        }

        // If clocked in but not out
        if (!$attendance->clock_out) {
            return 'present';
        }

        return 'completed';
    }

    /**
     * Handle unclosed attendance records
     */
    private function handleUncloseAttendance(Employee $employee, Carbon $now, string $today, Carbon $cutoffTime): void
    {
        // Find any unclosed attendance from previous days or today past cutoff
        $unclosed = Attendance::where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->where(function ($query) use ($today, $now, $cutoffTime) {
                // Previous days
                $query->whereDate('date', '<', $today)
                    // Or today but past 4 PM
                    ->orWhere(function ($q) use ($today, $now, $cutoffTime) {
                        $q->whereDate('date', $today)
                            ->where(DB::raw('1'), '=', $now->gte($cutoffTime) ? '1' : '0');
                    });
            })
            ->get();

        foreach ($unclosed as $record) {
            $recordDate = Carbon::parse($record->date);
            $autoClockOutTime = $recordDate->copy()->setTime(16, 0)->toTimeString();
            $record->clock_out = $autoClockOutTime;
            $record->total_hours = $record->calculateTotalHours();
            $record->notes = ($record->notes ? $record->notes . ' | ' : '') . 'Auto-clocked out by system';
            $record->save();

            Log::warning('Auto-clocked out unclosed attendance', [
                'employee_id' => $employee->id,
                'attendance_id' => $record->id,
                'original_date' => $record->date,
                'auto_clock_out' => $autoClockOutTime
            ]);
        }
    }

    /**
     * Get monthly summary
     */
    public function getMonthlySummary(int $employeeId, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $totalHours = $attendances->sum('total_hours');
        $workedDays = $attendances->count(); // Days with any attendance record
        $lateDays = $attendances->where('status', 'late')->count();
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        $absentDays = $workingDays - $workedDays;

        return [
            'total_hours' => round($totalHours, 2),
            'present_days' => $workedDays - $lateDays, // Non-late worked days
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'working_days' => $workingDays,
            'attendance_rate' => $workingDays > 0 ? round(($workedDays / $workingDays) * 100, 2) : 0,
        ];
    }

    /**
     * Get overtime hours
     */
    public function getOvertimeHours(int $employeeId, string $startDate, string $endDate): float
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
        $overtimeHours = 0;

        foreach ($attendances as $attendance) {
            $dailyOvertime = max(0, ($attendance->total_hours ?? 0) - $overtimeThreshold);
            $overtimeHours += $dailyOvertime;
        }

        return round($overtimeHours, 2);
    }

    /**
     * Get current statuses for all employees (for managers/admins)
     */
    public function getCurrentStatuses(): Collection
    {
        $today = now()->timezone('Africa/Lusaka')->toDateString();
        $employees = Employee::with(['attendance' => function ($query) use ($today) {
            $query->whereDate('date', $today);
        }])->get();

        return $employees->map(function ($employee) {
            $attendance = $employee->attendance;
            $status = 'absent';

            if ($attendance) {
                $status = $attendance->clock_out ? 'completed' : 'present';
            }

            $employee->current_status = $status;
            $employee->attendance_details = $attendance;

            return $employee;
        });
    }

    /**
     * Get working days in a month (excluding weekends)
     */
    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $days = 0;
        $date = Carbon::create($year, $month, 1);
        $daysInMonth = $date->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            if (!$currentDate->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Force close all open attendance records for an employee
     * Useful for fixing stuck states
     */
    public function forceCloseAllOpen(Employee $employee): int
    {
        $cutoffTime = '16:00:00';

        $updated = Attendance::where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->update([
                'clock_out' => DB::raw("CONCAT(date, ' ', '$cutoffTime')"),
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' | Force closed by system')"),
            ]);

        // Recalculate total hours for updated records
        $records = Attendance::where('employee_id', $employee->id)
            ->whereNotNull('clock_out')
            ->where(function ($query) {
                $query->whereNull('total_hours')
                    ->orWhere('total_hours', 0);
            })
            ->get();

        foreach ($records as $record) {
            $record->total_hours = $record->calculateTotalHours();
            $record->save();
        }

        Log::info('Force closed all open attendance records', [
            'employee_id' => $employee->id,
            'records_closed' => $updated
        ]);

        return $updated;
    }
}
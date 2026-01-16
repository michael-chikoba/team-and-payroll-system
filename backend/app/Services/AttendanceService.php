<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * Clock in an employee (checks for shift)
     */
    public function clockIn(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();
        $cutoffTime = $now->copy()->setTime(16, 0);

        // Handle unclosed attendance records
        $this->handleUncloseAttendance($employee, $now, $today, $cutoffTime);

        // Check for today's shift assignment
        $shift = ShiftAssignment::where('employee_id', $employee->id)
            ->whereDate('shift_date', $today)
            ->whereIn('status', ['accepted', 'pending'])
            ->first();

        Log::info('Clock-in attempt', [
            'employee_id' => $employee->id,
            'has_shift' => $shift ? 'yes' : 'no',
            'shift_id' => $shift ? $shift->id : null
        ]);

        // Check if already clocked in today
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->where('is_overtime_session', false) // Only check regular sessions
            ->first();

        if ($todayAttendance) {
            // Check if shift has ended - if yes, this could be overtime
            if ($todayAttendance->hasShiftEnded()) {
                throw new \Exception('Regular shift has ended. Please clock out first, then clock in again for overtime.');
            }
            
            throw new \Exception('Already clocked in today at ' . $todayAttendance->clock_in);
        }

        // Check if there's a completed attendance today (for overtime scenario)
        $completedToday = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_out')
            ->where('is_overtime_session', false)
            ->first();

        // Determine expected start time
        $expectedStartTime = $shift ? $shift->start_time : config('attendance.default_start_time', '08:00');
        $expectedIn = $now->copy()->setTimeFromTimeString($expectedStartTime);
        
        // Determine status: late if after grace period
        // FIX: Cast to integer to avoid TypeError
        $gracePeriod = (int) config('attendance.grace_period_minutes', 15);
        $lateThreshold = $expectedIn->copy()->addMinutes($gracePeriod);
        $status = ($now->greaterThan($lateThreshold)) ? 'late' : 'present';

        // Create new attendance record
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'shift_assignment_id' => $shift ? $shift->id : null,
            'date' => $today,
            'clock_in' => $now->toTimeString(),
            'status' => $status,
            'break_minutes' => 0,
            'is_overtime_session' => false,
            'parent_attendance_id' => null,
        ]);

        Log::info('Employee clocked in successfully', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'clock_in' => $attendance->clock_in,
            'status' => $status,
            'has_shift' => $shift ? 'yes' : 'no'
        ]);

        return $attendance->load('shiftAssignment');
    }

    /**
     * Clock out an employee
     */
    public function clockOut(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Find today's unclosed attendance (could be regular or overtime)
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->orderBy('created_at', 'desc') // Get the most recent unclosed session
            ->first();

        if (!$attendance) {
            throw new \Exception('No active clock-in found for today. Please clock in first.');
        }

        $attendance->clock_out = $now->toTimeString();
        
        // Calculate hours (this will automatically split regular/overtime)
        $hours = $attendance->calculateHours();
        $attendance->total_hours = $hours['total'];
        $attendance->regular_hours = $hours['regular'];
        $attendance->overtime_hours = $hours['overtime'];
        
        // Update status
        if ($attendance->is_overtime_session) {
            $attendance->status = 'completed';
        } else {
            $attendance->status = $attendance->isLateForShift() ? 'late' : 'completed';
        }
        
        $attendance->save();

        Log::info('Employee clocked out successfully', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'clock_out' => $attendance->clock_out,
            'total_hours' => $attendance->total_hours,
            'regular_hours' => $attendance->regular_hours,
            'overtime_hours' => $attendance->overtime_hours,
            'is_overtime_session' => $attendance->is_overtime_session
        ]);

        return $attendance->load('shiftAssignment');
    }

    /**
     * Clock in for overtime (after regular shift ends)
     */
    public function clockInOvertime(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Must have completed regular shift today
        $regularAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_out')
            ->where('is_overtime_session', false)
            ->first();

        if (!$regularAttendance) {
            throw new \Exception('You must complete your regular shift before starting overtime.');
        }

        // Check if already in an overtime session
        $activeOvertime = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->where('is_overtime_session', true)
            ->first();

        if ($activeOvertime) {
            throw new \Exception('You already have an active overtime session.');
        }

        // Create overtime attendance record
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'shift_assignment_id' => $regularAttendance->shift_assignment_id,
            'date' => $today,
            'clock_in' => $now->toTimeString(),
            'status' => 'present',
            'break_minutes' => 0,
            'is_overtime_session' => true,
            'parent_attendance_id' => $regularAttendance->id,
            'notes' => 'Overtime session'
        ]);

        Log::info('Employee clocked in for overtime', [
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'parent_attendance_id' => $regularAttendance->id,
            'clock_in' => $attendance->clock_in
        ]);

        return $attendance->load('shiftAssignment', 'parentAttendance');
    }

    /**
     * Get today's status with shift information
     */
    public function getTodayStatus(Employee $employee): array
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();
        $cutoffTime = $now->copy()->setTime(16, 0);

        // Handle unclosed attendance
        $this->handleUncloseAttendance($employee, $now, $today, $cutoffTime);

        // Get today's shift
        $shift = ShiftAssignment::where('employee_id', $employee->id)
            ->whereDate('shift_date', $today)
            ->whereIn('status', ['accepted', 'pending'])
            ->first();

        // Get all today's attendance records
        $regularAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->where('is_overtime_session', false)
            ->first();

        $overtimeAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->where('is_overtime_session', true)
            ->whereNull('clock_out')
            ->first();

        // Determine status
        $status = 'absent';
        $canClockIn = true;
        $canClockOut = false;
        $canStartOvertime = false;
        $isInOvertimeSession = false;
        $shiftHasEnded = false;

        if ($regularAttendance) {
            if (!$regularAttendance->clock_out) {
                // Currently clocked in (regular shift)
                $status = 'present';
                $canClockIn = false;
                $canClockOut = true;
                
                // Check if shift has ended (can start overtime after clocking out)
                $shiftHasEnded = $regularAttendance->hasShiftEnded();
                
            } else {
                // Completed regular shift
                $status = 'completed';
                $canClockIn = false;
                $canClockOut = false;
                $canStartOvertime = true; // Can clock in for overtime
                $shiftHasEnded = true;
            }
        }

        // Check overtime status
        if ($overtimeAttendance) {
            $status = 'present'; // In overtime session
            $canClockIn = false;
            $canClockOut = true;
            $canStartOvertime = false;
            $isInOvertimeSession = true;
        }

        return [
            'status' => $status,
            'regular_attendance' => $regularAttendance,
            'overtime_attendance' => $overtimeAttendance,
            'shift' => $shift,
            'can_clock_in' => $canClockIn,
            'can_clock_out' => $canClockOut,
            'can_start_overtime' => $canStartOvertime,
            'is_in_overtime_session' => $isInOvertimeSession,
            'shift_has_ended' => $shiftHasEnded,
            'expected_shift_end_time' => $regularAttendance ? $regularAttendance->getExpectedShiftEndTime() : null
        ];
    }

    /**
     * Handle unclosed attendance records
     */
    private function handleUncloseAttendance(Employee $employee, Carbon $now, string $today, Carbon $cutoffTime): void
    {
        $unclosed = Attendance::where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->where(function ($query) use ($today, $now, $cutoffTime) {
                $query->whereDate('date', '<', $today)
                    ->orWhere(function ($q) use ($today, $now, $cutoffTime) {
                        $q->whereDate('date', $today)
                            ->where(DB::raw('1'), '=', $now->gte($cutoffTime) ? '1' : '0');
                    });
            })
            ->get();

        foreach ($unclosed as $record) {
            $recordDate = Carbon::parse($record->date);
            
            // Determine auto-clock-out time based on shift or default
            if ($record->shiftAssignment) {
                $autoClockOutTime = $record->shiftAssignment->end_time;
            } else {
                $autoClockOutTime = '16:00:00'; // Default 4 PM
            }
            
            $record->clock_out = $autoClockOutTime;
            
            // Calculate hours
            $hours = $record->calculateHours();
            $record->total_hours = $hours['total'];
            $record->regular_hours = $hours['regular'];
            $record->overtime_hours = $hours['overtime'];
            
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
     * Get monthly summary with overtime breakdown
     */
    public function getMonthlySummary(int $employeeId, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $regularAttendances = $attendances->where('is_overtime_session', false);
        $overtimeAttendances = $attendances->where('is_overtime_session', true);

        $totalHours = $attendances->sum('total_hours');
        $regularHours = $attendances->sum('regular_hours');
        $overtimeHours = $attendances->sum('overtime_hours');
        
        $workedDays = $regularAttendances->count();
        $lateDays = $regularAttendances->where('status', 'late')->count();
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        $absentDays = $workingDays - $workedDays;

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'present_days' => $workedDays - $lateDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'overtime_sessions' => $overtimeAttendances->count(),
            'working_days' => $workingDays,
            'attendance_rate' => $workingDays > 0 ? round(($workedDays / $workingDays) * 100, 2) : 0,
        ];
    }

    /**
     * Get overtime hours for date range
     */
    public function getOvertimeHours(int $employeeId, string $startDate, string $endDate): float
    {
        $overtimeHours = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('overtime_hours');

        return round($overtimeHours, 2);
    }

    /**
     * Get current statuses for all employees
     */
    public function getCurrentStatuses(): Collection
    {
        $today = now()->timezone('Africa/Lusaka')->toDateString();
        $employees = Employee::with(['attendance' => function ($query) use ($today) {
            $query->whereDate('date', $today)
                ->where('is_overtime_session', false); // Only show regular attendance status
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
     * Force close all open attendance records
     */
    public function forceCloseAllOpen(Employee $employee): int
    {
        $unclosed = Attendance::where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->get();

        $updated = 0;

        foreach ($unclosed as $record) {
            $recordDate = Carbon::parse($record->date);
            
            if ($record->shiftAssignment) {
                $autoClockOutTime = $record->shiftAssignment->end_time;
            } else {
                $autoClockOutTime = '16:00:00';
            }

            $record->clock_out = $autoClockOutTime;
            $hours = $record->calculateHours();
            $record->total_hours = $hours['total'];
            $record->regular_hours = $hours['regular'];
            $record->overtime_hours = $hours['overtime'];
            $record->notes = ($record->notes ? $record->notes . ' | ' : '') . 'Force closed by system';
            $record->save();
            
            $updated++;
        }

        Log::info('Force closed all open attendance records', [
            'employee_id' => $employee->id,
            'records_closed' => $updated
        ]);

        return $updated;
    }

    /**
     * Get working days in a month
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
}
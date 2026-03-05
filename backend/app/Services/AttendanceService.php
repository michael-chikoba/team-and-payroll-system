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

        // Handle unclosed attendance records
        $this->handleUncloseAttendance($employee, $now, $today);

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

        // Check if already clocked in today (regular session)
        $todayRegularAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->where('is_overtime_session', false)
            ->first();

        if ($todayRegularAttendance) {
            throw new \Exception('Already clocked in today at ' . $todayRegularAttendance->clock_in);
        }

        // Check if there's a completed regular session today (for overtime)
        $completedRegular = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNotNull('clock_out')
            ->where('is_overtime_session', false)
            ->first();

        // Determine expected start time
        $expectedStartTime = $shift ? $shift->start_time : config('attendance.default_start_time', '08:00');
        $expectedIn = Carbon::parse($today . ' ' . $expectedStartTime, 'Africa/Lusaka');
        
        // Determine status: late if after grace period
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
            'type' => 'regular'
        ]);

        return $attendance->load('shiftAssignment');
    }

    /**
     * Clock out from regular session only
     */
    public function clockOut(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Find today's unclosed regular attendance
        $regularAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->where('is_overtime_session', false)
            ->first();

        if (!$regularAttendance) {
            throw new \Exception('No active regular session found for today.');
        }

        $regularAttendance->clock_out = $now->toTimeString();
        
        // Calculate hours
        $hours = $regularAttendance->calculateHours();
        $regularAttendance->total_hours = $hours['total'];
        $regularAttendance->regular_hours = $hours['regular'];
        $regularAttendance->overtime_hours = $hours['overtime'];
        
        // Preserve original status
        if ($regularAttendance->status === 'absent') {
            $regularAttendance->status = $regularAttendance->isLateForShift() ? 'late' : 'present';
        }
        
        $regularAttendance->save();

        Log::info('Regular session clocked out', [
            'employee_id' => $employee->id,
            'attendance_id' => $regularAttendance->id,
            'clock_out' => $regularAttendance->clock_out,
            'total_hours' => $regularAttendance->total_hours,
            'status' => $regularAttendance->status
        ]);

        return $regularAttendance->load('shiftAssignment');
    }

    /**
     * Clock out from overtime session
     */
    public function clockOutOvertime(Employee $employee): Attendance
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Find today's unclosed overtime attendance
        $overtimeAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNull('clock_out')
            ->where('is_overtime_session', true)
            ->first();

        if (!$overtimeAttendance) {
            throw new \Exception('No active overtime session found.');
        }

        $overtimeAttendance->clock_out = $now->toTimeString();
        
        // Calculate hours (all hours are overtime for overtime sessions)
        $hours = $overtimeAttendance->calculateHours();
        $overtimeAttendance->total_hours = $hours['total'];
        $overtimeAttendance->regular_hours = 0;
        $overtimeAttendance->overtime_hours = $hours['total'];
        $overtimeAttendance->status = 'completed';
        
        $overtimeAttendance->save();

        Log::info('Overtime session clocked out', [
            'employee_id' => $employee->id,
            'attendance_id' => $overtimeAttendance->id,
            'clock_out' => $overtimeAttendance->clock_out,
            'total_hours' => $overtimeAttendance->total_hours,
            'parent_id' => $overtimeAttendance->parent_attendance_id
        ]);

        return $overtimeAttendance->load('shiftAssignment', 'parentAttendance');
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
            'last_activity_at' => $now,
            'notes' => 'Overtime session started'
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
     * Get today's status with proper session separation
     */
    public function getTodayStatus(Employee $employee): array
    {
        $now = now()->timezone('Africa/Lusaka');
        $today = $now->toDateString();

        // Handle unclosed attendance
        $this->handleUncloseAttendance($employee, $now, $today);

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
        $canClockOutRegular = false;
        $canClockOutOvertime = false;
        $canStartOvertime = false;
        $isInOvertimeSession = false;
        $shiftHasEnded = false;

        // Regular session scenarios
        if ($regularAttendance) {
            if (!$regularAttendance->clock_out) {
                // Currently in regular session
                $status = $regularAttendance->status; // 'late' or 'present'
                $canClockIn = false;
                $canClockOutRegular = true;
                $shiftHasEnded = $regularAttendance->hasShiftEnded();
                
            } else {
                // Regular session completed
                $status = $regularAttendance->status; // 'late' or 'present'
                $canClockIn = false;
                $canClockOutRegular = false;
                $canStartOvertime = true; // Can start overtime
                $shiftHasEnded = true;
            }
        }

        // Overtime session scenarios (overrides regular session status)
        if ($overtimeAttendance) {
            $status = 'on_overtime';
            $canClockIn = false;
            $canClockOutRegular = false; // Can't clock out regular from overtime
            $canClockOutOvertime = true; // Can clock out from overtime
            $canStartOvertime = false; // Already in overtime
            $isInOvertimeSession = true;
        }

        // Special case: Regular completed + No overtime = Ready for overtime
        if ($regularAttendance && $regularAttendance->clock_out && !$overtimeAttendance) {
            $status = 'ready_for_overtime';
        }

        Log::debug('Today status calculated', [
            'employee_id' => $employee->id,
            'has_regular' => !is_null($regularAttendance),
            'regular_closed' => $regularAttendance ? !is_null($regularAttendance->clock_out) : null,
            'has_overtime' => !is_null($overtimeAttendance),
            'final_status' => $status,
            'can_clock_out_regular' => $canClockOutRegular,
            'can_clock_out_overtime' => $canClockOutOvertime,
            'can_start_overtime' => $canStartOvertime
        ]);

        return [
            'status' => $status,
            'regular_attendance' => $regularAttendance,
            'overtime_attendance' => $overtimeAttendance,
            'shift' => $shift,
            'can_clock_in' => $canClockIn,
            'can_clock_out_regular' => $canClockOutRegular,
            'can_clock_out_overtime' => $canClockOutOvertime,
            'can_start_overtime' => $canStartOvertime,
            'is_in_overtime_session' => $isInOvertimeSession,
            'shift_has_ended' => $shiftHasEnded,
            'expected_shift_end_time' => $regularAttendance ? $regularAttendance->getExpectedShiftEndTime() : null
        ];
    }

    /**
     * Handle unclosed attendance records
     */
    private function handleUncloseAttendance(Employee $employee, Carbon $now, string $today): void
    {
        // Only auto-close records from previous days, not today's
        $unclosed = Attendance::where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->whereDate('date', '<', $today)
            ->get();

        foreach ($unclosed as $record) {
            $recordDate = Carbon::parse($record->date);
            
            // Determine auto-clock-out time
            if ($record->shiftAssignment) {
                $autoClockOutTime = $record->shiftAssignment->end_time;
            } else {
                $autoClockOutTime = '16:00:00';
            }
            
            $record->clock_out = $autoClockOutTime;
            
            // Calculate hours
            $hours = $record->calculateHours();
            $record->total_hours = $hours['total'];
            $record->regular_hours = $hours['regular'];
            $record->overtime_hours = $hours['overtime'];
            
            if ($record->status === 'absent') {
                $record->status = 'present';
            }
            
            $record->notes = ($record->notes ? $record->notes . ' | ' : '') . 
                'Auto-clocked out for previous day: ' . $recordDate->toDateString();
            $record->save();

            Log::warning('Auto-clocked out previous day attendance', [
                'employee_id' => $employee->id,
                'attendance_id' => $record->id,
                'date' => $record->date,
                'type' => $record->is_overtime_session ? 'overtime' : 'regular'
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
        
        $presentDays = $regularAttendances->where('status', 'present')->count();
        $lateDays = $regularAttendances->where('status', 'late')->count();
        $workedDays = $presentDays + $lateDays;
        
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        $absentDays = max(0, $workingDays - $workedDays);

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'worked_days' => $workedDays,
            'overtime_sessions' => $overtimeAttendances->count(),
            'working_days' => $workingDays,
            'attendance_rate' => $workingDays > 0 ? round(($workedDays / $workingDays) * 100, 2) : 0,
        ];
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
     * FIXED: Shows actual status (late/present) not just 'present'
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
            
            // Default status is absent
            $status = 'absent';
            
            if ($attendance) {
                // Use the actual status from the record (late, present, on_leave, etc.)
                $status = $attendance->status;
            }

            $employee->current_status = $status;
            $employee->attendance_details = $attendance;
            
            return $employee;
        });
    }

    /**
     * Force close all open attendance records
     * FIXED: Preserves status when force closing
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
            
            // CRITICAL FIX: Preserve status (late/present)
            if ($record->status === 'absent') {
                $record->status = 'present';
            }
            // Otherwise keep existing status
            
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
 * Return overtime hours broken down by weekday vs weekend / public holiday
 * for a given employee over a payroll period.
 *
 * Called by:
 *   - PayrollCalculationService::calculateOvertimeData()  (automatic path)
 *   - PayrollController::overtimeBreakdown()              (frontend AJAX endpoint)
 *
 * Overtime minutes come from two sources on each attendance row:
 *   a) Regular session  → overtime_hours column (hours worked beyond shift)
 *   b) Overtime session → total_hours column    (the whole session is OT)
 *
 * Day classification:
 *   - Weekend (Sat / Sun)                           → 2.5× rate
 *   - Public holiday (date in public_holidays table → 2.5× rate
 *   - Any other weekday                             → 1.5× rate
 *
 * @param  int    $employeeId
 * @param  string $startDate   Y-m-d
 * @param  string $endDate     Y-m-d
 * @return array{
 *   weekday_hours: float,
 *   weekday_pay: float,
 *   weekday_rate: float,
 *   weekend_ph_hours: float,
 *   weekend_ph_pay: float,
 *   weekend_ph_rate: float,
 *   hourly_rate: float,
 *   total_hours: float,
 *   total_pay: float,
 *   source: string,
 * }
 */
public function getOvertimeHoursBreakdown(int $employeeId, string $startDate, string $endDate): array
{
    // ── 1. Resolve hourly rate from employee's base salary ─────────────────
    $employee   = \App\Models\Employee::find($employeeId);
    $baseSalary = $employee ? (float) $employee->base_salary : 0.0;
    $hourlyRate = $baseSalary > 0 ? round($baseSalary / 173.33, 4) : 0.0;

    // ── 2. Load public holidays that fall within the period ────────────────
    //      Gracefully degrade if the table doesn't exist yet.
    $publicHolidays = [];
    try {
        $publicHolidays = \App\Models\PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::warning(
            'AttendanceService::getOvertimeHoursBreakdown — could not query public_holidays table',
            ['error' => $e->getMessage()]
        );
    }

    // ── 3. Fetch all attendance rows that contain overtime ─────────────────
    //      We pull BOTH regular sessions (overtime_hours > 0) AND dedicated
    //      overtime sessions (is_overtime_session = true, total_hours > 0).
    $records = \App\Models\Attendance::where('employee_id', $employeeId)
        ->whereBetween('date', [$startDate, $endDate])
        ->where(function ($q) {
            $q->where(function ($inner) {
                // Regular session that ran into overtime
                $inner->where('is_overtime_session', false)
                      ->where('overtime_hours', '>', 0);
            })->orWhere(function ($inner) {
                // Dedicated overtime clock-in session
                $inner->where('is_overtime_session', true)
                      ->where('total_hours', '>', 0);
            });
        })
        ->get();

    // ── 4. Bucket hours into weekday vs weekend/PH ─────────────────────────
    $weekdayMinutes   = 0.0;
    $weekendPhMinutes = 0.0;

    foreach ($records as $record) {
        $date = \Carbon\Carbon::parse($record->date);

        // Determine overtime hours for this row
        if ($record->is_overtime_session) {
            $otHours = (float) ($record->total_hours ?? 0);
        } else {
            $otHours = (float) ($record->overtime_hours ?? 0);
        }

        $otMinutes = $otHours * 60;

        $isWeekend   = $date->isWeekend();
        $isPublicHol = in_array($date->toDateString(), $publicHolidays, true);

        if ($isWeekend || $isPublicHol) {
            $weekendPhMinutes += $otMinutes;
        } else {
            $weekdayMinutes += $otMinutes;
        }
    }

    // ── 5. Convert to hours and calculate pay ──────────────────────────────
    $weekdayHours   = round($weekdayMinutes   / 60, 2);
    $weekendPhHours = round($weekendPhMinutes / 60, 2);

    $weekdayRate    = round($hourlyRate * 1.5, 4);
    $weekendPhRate  = round($hourlyRate * 2.5, 4);

    $weekdayPay   = round($weekdayHours   * $weekdayRate,   2);
    $weekendPhPay = round($weekendPhHours * $weekendPhRate, 2);

    $totalHours = round($weekdayHours + $weekendPhHours, 2);
    $totalPay   = round($weekdayPay   + $weekendPhPay,   2);

    \Illuminate\Support\Facades\Log::debug(
        'AttendanceService::getOvertimeHoursBreakdown',
        [
            'employee_id'      => $employeeId,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'base_salary'      => $baseSalary,
            'hourly_rate'      => $hourlyRate,
            'records_found'    => $records->count(),
            'public_holidays'  => count($publicHolidays),
            'weekday_hours'    => $weekdayHours,
            'weekend_ph_hours' => $weekendPhHours,
            'total_pay'        => $totalPay,
        ]
    );

    return [
        'source'           => 'attendance',
        'hourly_rate'      => $hourlyRate,

        // Weekday (×1.5)
        'weekday_hours'    => $weekdayHours,
        'weekday_rate'     => $weekdayRate,
        'weekday_pay'      => $weekdayPay,

        // Weekend / Public Holiday (×2.5)
        'weekend_ph_hours' => $weekendPhHours,
        'weekend_ph_rate'  => $weekendPhRate,
        'weekend_ph_pay'   => $weekendPhPay,

        // Totals
        'total_hours'      => $totalHours,
        'total_pay'        => $totalPay,
    ];
}
}
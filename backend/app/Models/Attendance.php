<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_assignment_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'break_minutes',
        'status',
        'notes',
        'country_id',
        'business_id',
        'is_overtime_session', // NEW: Flag for overtime sessions
        'parent_attendance_id', // NEW: Link to original attendance if this is overtime
    ];

    protected $casts = [
        'date' => 'date',
        'total_hours' => 'float',
        'regular_hours' => 'float',
        'overtime_hours' => 'float',
        'break_minutes' => 'integer',
        'is_overtime_session' => 'boolean',
    ];

    protected $appends = ['full_date'];

    /**
     * Boot method for the model
     */
    protected static function booted()
    {
        static::creating(function ($attendance) {
            // Auto-populate country_id and business_id from employee
            if ($attendance->employee) {
                $attendance->country_id = $attendance->employee->country_id;
                $attendance->business_id = $attendance->employee->business_id;
            }
        });

        static::saving(function ($attendance) {
            // Always recalculate if both times are present
            if ($attendance->clock_in && $attendance->clock_out) {
                $hours = $attendance->calculateHours();
                $attendance->total_hours = $hours['total'];
                $attendance->regular_hours = $hours['regular'];
                $attendance->overtime_hours = $hours['overtime'];
                
                Log::debug('Auto-updating hours', [
                    'attendance_id' => $attendance->id,
                    'total_hours' => $hours['total'],
                    'regular_hours' => $hours['regular'],
                    'overtime_hours' => $hours['overtime']
                ]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // NEW: Parent attendance relationship (for overtime sessions)
    public function parentAttendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'parent_attendance_id');
    }

    // NEW: Child overtime sessions
    public function overtimeSessions()
    {
        return $this->hasMany(Attendance::class, 'parent_attendance_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Attributes
    |--------------------------------------------------------------------------
    */

    public function getFullDateAttribute(): string
    {
        return $this->date ? Carbon::parse($this->date)->format('Y-m-d') : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Hours Calculation with Shift Support
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate hours with shift-based overtime detection
     */
    public function calculateHours(): array
    {
        if (!$this->clock_in || !$this->clock_out) {
            return [
                'total' => 0,
                'regular' => 0,
                'overtime' => 0
            ];
        }

        try {
            $dateStr = $this->date instanceof Carbon
                ? $this->date->format('Y-m-d')
                : Carbon::parse($this->date)->format('Y-m-d');

            $clockIn = Carbon::parse($dateStr . ' ' . $this->clock_in, 'Africa/Lusaka');
            $clockOut = Carbon::parse($dateStr . ' ' . $this->clock_out, 'Africa/Lusaka');

            // Handle overnight shifts
            if ($clockOut->lessThanOrEqualTo($clockIn)) {
                $clockOut->addDay();
            }

            // Calculate total minutes
            $totalMinutes = abs($clockIn->diffInMinutes($clockOut, false));
            $breakMinutes = $this->break_minutes ?? 0;
            $workMinutes = max(0, $totalMinutes - $breakMinutes);
            $totalHours = round($workMinutes / 60, 2);

            // If this is already marked as an overtime session, all hours are overtime
            if ($this->is_overtime_session) {
                return [
                    'total' => $totalHours,
                    'regular' => 0,
                    'overtime' => $totalHours
                ];
            }

            // Get shift or use default
            $shift = $this->shiftAssignment;
            $expectedEndTime = null;
            
            if ($shift) {
                // Use shift end time
                $expectedEndTime = Carbon::parse($dateStr . ' ' . $shift->end_time, 'Africa/Lusaka');
            } else {
                // Default: 8 hours from start (08:00 - 16:00)
                $defaultStartTime = config('attendance.default_start_time', '08:00');
                $defaultHours = config('attendance.regular_hours', 8);
                $startTime = Carbon::parse($dateStr . ' ' . $defaultStartTime, 'Africa/Lusaka');
                $expectedEndTime = $startTime->copy()->addHours($defaultHours);
            }

            // Calculate regular vs overtime
            if ($clockOut->lessThanOrEqualTo($expectedEndTime)) {
                // All hours are regular
                return [
                    'total' => $totalHours,
                    'regular' => $totalHours,
                    'overtime' => 0
                ];
            } else {
                // Split into regular and overtime
                $regularMinutes = $clockIn->diffInMinutes($expectedEndTime);
                $overtimeMinutes = $expectedEndTime->diffInMinutes($clockOut);
                
                // Subtract break proportionally
                $regularWithBreak = max(0, $regularMinutes - ($breakMinutes * ($regularMinutes / $totalMinutes)));
                $overtimeWithBreak = max(0, $overtimeMinutes - ($breakMinutes * ($overtimeMinutes / $totalMinutes)));
                
                $regularHours = round($regularWithBreak / 60, 2);
                $overtimeHours = round($overtimeWithBreak / 60, 2);
                
                return [
                    'total' => $totalHours,
                    'regular' => $regularHours,
                    'overtime' => $overtimeHours
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error calculating hours', [
                'attendance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'regular' => 0,
                'overtime' => 0
            ];
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function calculateTotalHours(): float
    {
        $hours = $this->calculateHours();
        return $hours['total'];
    }

    /*
    |--------------------------------------------------------------------------
    | Shift-Based Status Detection
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if employee is late based on shift
     */
    public function isLateForShift(): bool
    {
        if (!$this->clock_in) {
            return false;
        }

        $shift = $this->shiftAssignment;
        $dateStr = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : Carbon::parse($this->date)->format('Y-m-d');
        
        if ($shift) {
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
        } else {
            // Use default start time
            $defaultStartTime = config('attendance.default_start_time', '08:00');
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $defaultStartTime);
        }
        
        $actualClockIn = Carbon::parse($dateStr . ' ' . $this->clock_in);
        $gracePeriod = config('attendance.grace_period_minutes', 15);
        $lateThreshold = $shiftStartTime->copy()->addMinutes($gracePeriod);

        return $actualClockIn->greaterThan($lateThreshold);
    }

    /**
     * Get minutes late
     */
    public function getMinutesLate(): int
    {
        if (!$this->clock_in) {
            return 0;
        }

        $shift = $this->shiftAssignment;
        $dateStr = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : Carbon::parse($this->date)->format('Y-m-d');
        
        if ($shift) {
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $shift->start_time);
        } else {
            $defaultStartTime = config('attendance.default_start_time', '08:00');
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $defaultStartTime);
        }
        
        $actualClockIn = Carbon::parse($dateStr . ' ' . $this->clock_in);
        
        if ($actualClockIn->lessThanOrEqualTo($shiftStartTime)) {
            return 0;
        }

        return $actualClockIn->diffInMinutes($shiftStartTime);
    }

    /**
     * Check if shift has ended (can clock out or start overtime)
     */
    public function hasShiftEnded(): bool
    {
        if (!$this->clock_in) {
            return false;
        }

        $now = Carbon::now('Africa/Lusaka');
        $shift = $this->shiftAssignment;
        $dateStr = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : Carbon::parse($this->date)->format('Y-m-d');
        
        if ($shift) {
            $shiftEndTime = Carbon::parse($dateStr . ' ' . $shift->end_time);
        } else {
            // Default: 8 hours from default start
            $defaultStartTime = config('attendance.default_start_time', '08:00');
            $defaultHours = config('attendance.regular_hours', 8);
            $startTime = Carbon::parse($dateStr . ' ' . $defaultStartTime);
            $shiftEndTime = $startTime->copy()->addHours($defaultHours);
        }

        return $now->greaterThanOrEqualTo($shiftEndTime);
    }

    /**
     * Get expected shift end time
     */
    public function getExpectedShiftEndTime(): ?Carbon
    {
        $shift = $this->shiftAssignment;
        $dateStr = $this->date instanceof Carbon
            ? $this->date->format('Y-m-d')
            : Carbon::parse($this->date)->format('Y-m-d');
        
        if ($shift) {
            return Carbon::parse($dateStr . ' ' . $shift->end_time, 'Africa/Lusaka');
        } else {
            // Default: 8 hours from default start
            $defaultStartTime = config('attendance.default_start_time', '08:00');
            $defaultHours = config('attendance.regular_hours', 8);
            $startTime = Carbon::parse($dateStr . ' ' . $defaultStartTime, 'Africa/Lusaka');
            return $startTime->copy()->addHours($defaultHours);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get monthly statistics with overtime breakdown
     */
    public static function getMonthlyStats(int $employeeId, int $month, int $year): array
    {
        try {
            $attendances = self::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $completedShifts = $attendances->whereNotNull('clock_out');
            $totalHours = $completedShifts->sum('total_hours');
            $regularHours = $completedShifts->sum('regular_hours');
            $overtimeHours = $completedShifts->sum('overtime_hours');
            
            $avgHoursPerDay = $completedShifts->count() > 0 
                ? $totalHours / $completedShifts->count() 
                : 0;

            return [
                'total_hours' => round($totalHours, 2),
                'regular_hours' => round($regularHours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'total_days' => $attendances->count(),
                'completed_days' => $completedShifts->count(),
                'incomplete_days' => $attendances->whereNull('clock_out')->count(),
                'average_hours_per_day' => round($avgHoursPerDay, 2),
                'present_days' => $attendances->whereIn('status', ['present', 'completed'])->count(),
                'late_days' => $attendances->where('status', 'late')->count(),
                'absent_days' => $attendances->where('status', 'absent')->count(),
                'overtime_sessions' => $attendances->where('is_overtime_session', true)->count(),
                'month' => $month,
                'year' => $year,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting monthly stats', [
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->timezone('Africa/Lusaka')->toDateString());
    }

    public function scopeUnclosed($query)
    {
        return $query->whereNull('clock_out');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('clock_out');
    }

    public function scopeRegularSessions($query)
    {
        return $query->where('is_overtime_session', false);
    }

    public function scopeOvertimeSessions($query)
    {
        return $query->where('is_overtime_session', true);
    }
}
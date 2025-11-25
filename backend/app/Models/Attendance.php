<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'total_hours',
        'break_minutes',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'total_hours' => 'float',
        'break_minutes' => 'integer',
    ];

    /**
     * Get the employee that owns the attendance
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate total hours worked for a single day
     * Enhanced with better error handling
     */
    public function calculateTotalHours(): float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        try {
            // Get the date for this attendance record
            $dateStr = $this->date instanceof Carbon
                ? $this->date->format('Y-m-d')
                : Carbon::parse($this->date)->format('Y-m-d');

            // Create Carbon instances for clock in and out
            $clockIn = Carbon::parse($dateStr . ' ' . $this->clock_in, 'Africa/Lusaka');
            $clockOut = Carbon::parse($dateStr . ' ' . $this->clock_out, 'Africa/Lusaka');

            // If clock out is before clock in, assume it's next day (overnight shift)
            if ($clockOut->lessThanOrEqualTo($clockIn)) {
                $clockOut->addDay();
            }

            // Calculate difference in minutes (ensure positive)
            $totalMinutes = abs($clockIn->diffInMinutes($clockOut, false));

            // Subtract break minutes
            $breakMinutes = $this->break_minutes ?? 0;
            $workMinutes = max(0, $totalMinutes - $breakMinutes);

            // Convert to hours
            $hours = round($workMinutes / 60, 2);

            return $hours;

        } catch (\Exception $e) {
            // Silent fail - return 0 on error
            return 0;
        }
    }

    /**
     * Parse time string to Carbon instance with proper date handling
     */
    private function parseTimeWithDate(string $time, string $date): Carbon
    {
        try {
            // Remove any timezone info that might be present
            $time = trim($time);
            
            // If time contains full datetime, parse directly
            if (strpos($time, ' ') !== false || strpos($time, 'T') !== false) {
                return Carbon::parse($time)->timezone('Africa/Lusaka');
            }

            // Time only format - ensure it's HH:MM:SS
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
                // Add seconds if missing
                if (substr_count($time, ':') === 1) {
                    $time .= ':00';
                }
                // Combine with date
                return Carbon::parse($date . ' ' . $time, 'Africa/Lusaka');
            }

            // Fallback: try parsing as-is
            return Carbon::parse($date . ' ' . $time, 'Africa/Lusaka');

        } catch (\Exception $e) {
            Log::error('Error parsing time', [
                'time' => $time,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get monthly total hours for an employee
     * Calculates sum of all hours worked in a specific month
     */
    public static function getMonthlyTotalHours(int $employeeId, int $month, int $year): float
    {
        try {
            $totalHours = self::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->whereNotNull('clock_out') // Only count completed shifts
                ->sum('total_hours');

            return round($totalHours, 2);

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get monthly statistics for an employee
     * Returns comprehensive breakdown of hours and attendance
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
            $avgHoursPerDay = $completedShifts->count() > 0 
                ? $totalHours / $completedShifts->count() 
                : 0;

            return [
                'total_hours' => round($totalHours, 2),
                'total_days' => $attendances->count(),
                'completed_days' => $completedShifts->count(),
                'incomplete_days' => $attendances->whereNull('clock_out')->count(),
                'average_hours_per_day' => round($avgHoursPerDay, 2),
                'present_days' => $attendances->where('status', 'present')->count(),
                'late_days' => $attendances->where('status', 'late')->count(),
                'absent_days' => $attendances->where('status', 'absent')->count(),
                'month' => $month,
                'year' => $year,
            ];

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get date range total hours
     * Useful for payroll calculations between specific dates
     */
    public static function getDateRangeTotalHours(
        int $employeeId, 
        string $startDate, 
        string $endDate
    ): float {
        try {
            $totalHours = self::where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('clock_out')
                ->sum('total_hours');

            return round($totalHours, 2);

        } catch (\Exception $e) {
            Log::error('Error calculating date range hours', [
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get overtime hours for a period
     */
    public function getOvertimeHours(): float
    {
        $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
        $totalHours = $this->total_hours ?? $this->calculateTotalHours();

        return max(0, round($totalHours - $overtimeThreshold, 2));
    }

    /**
     * Get monthly overtime hours
     */
    public static function getMonthlyOvertimeHours(
        int $employeeId, 
        int $month, 
        int $year
    ): float {
        try {
            $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
            
            $attendances = self::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->whereNotNull('clock_out')
                ->get();

            $totalOvertime = 0;
            foreach ($attendances as $attendance) {
                $dailyOvertime = max(0, $attendance->total_hours - $overtimeThreshold);
                $totalOvertime += $dailyOvertime;
            }

            return round($totalOvertime, 2);

        } catch (\Exception $e) {
            Log::error('Error calculating monthly overtime', [
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Recalculate and update all hours for records with zero hours
     * Useful for fixing data issues
     */
    public static function recalculateAllHours(int $employeeId = null): int
    {
        try {
            $query = self::whereNotNull('clock_out')
                ->where(function($q) {
                    $q->whereNull('total_hours')
                      ->orWhere('total_hours', 0)
                      ->orWhere('total_hours', '0.00');
                });

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            $records = $query->get();
            $updated = 0;

            foreach ($records as $record) {
                $record->total_hours = $record->calculateTotalHours();
                if ($record->save()) {
                    $updated++;
                }
            }

            Log::info('Recalculated attendance hours', [
                'employee_id' => $employeeId,
                'records_updated' => $updated
            ]);

            return $updated;

        } catch (\Exception $e) {
            Log::error('Error recalculating hours', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Scope to get attendance for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get unclosed attendance
     */
    public function scopeUnclosed($query)
    {
        return $query->whereNull('clock_out');
    }

    /**
     * Scope to get today's attendance
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->timezone('Africa/Lusaka')->toDateString());
    }

    /**
     * Scope to get completed attendance (with clock out)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('clock_out');
    }

    /**
     * Auto-calculate and update total hours before saving
     */
    protected static function booted()
    {
        static::saving(function ($attendance) {
            // Always recalculate if both times are present
            if ($attendance->clock_in && $attendance->clock_out) {
                $calculatedHours = $attendance->calculateTotalHours();
                
                // Update if different or if not set
                if (!$attendance->total_hours || 
                    abs($attendance->total_hours - $calculatedHours) > 0.01) {
                    $attendance->total_hours = $calculatedHours;
                    
                    Log::debug('Auto-updating total hours', [
                        'attendance_id' => $attendance->id,
                        'old_hours' => $attendance->getOriginal('total_hours'),
                        'new_hours' => $calculatedHours
                    ]);
                }
            }
        });
    }
}
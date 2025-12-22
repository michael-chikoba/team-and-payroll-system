<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;


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
        'country_id',
        'business_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_hours' => 'float',
        'break_minutes' => 'integer',
    ];

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

        static::updating(function ($attendance) {
            // Ensure consistency
            if ($attendance->employee) {
                $attendance->country_id = $attendance->employee->country_id;
                $attendance->business_id = $attendance->employee->business_id;
            }
        });

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

    /**
     * Get the employee that owns the attendance
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the country relationship
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the business relationship
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Add scopes for filtering
    public function scopeFilterByCountry($query, $countryId = null)
    {
        if ($countryId) {
            return $query->where('country_id', $countryId);
        }
        return $query;
    }

    public function scopeFilterByBusiness($query, $businessId = null)
    {
        if ($businessId) {
            return $query->where('business_id', $businessId);
        }
        return $query;
    }

    public function scopeFilterByDepartment($query, $department = null)
    {
        if ($department) {
            return $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }
        return $query;
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
            Log::error('Error calculating total hours', [
                'attendance_id' => $this->id,
                'clock_in' => $this->clock_in,
                'clock_out' => $this->clock_out,
                'error' => $e->getMessage()
            ]);
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
            Log::error('Error getting monthly total hours', [
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
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
                'present_days' => $attendances->whereIn('status', ['present', 'completed'])->count(),
                'late_days' => $attendances->where('status', 'late')->count(),
                'absent_days' => $attendances->where('status', 'absent')->count(),
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

    /**
     * Get monthly statistics with country/business filters
     */
    public static function getMonthlyStatsWithFilters(
        int $month, 
        int $year, 
        ?int $countryId = null, 
        ?int $businessId = null
    ): array {
        try {
            $query = self::whereYear('date', $year)
                ->whereMonth('date', $month);

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $attendances = $query->get();

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
                'present_days' => $attendances->whereIn('status', ['present', 'completed'])->count(),
                'late_days' => $attendances->where('status', 'late')->count(),
                'absent_days' => $attendances->where('status', 'absent')->count(),
                'employee_count' => $attendances->groupBy('employee_id')->count(),
                'month' => $month,
                'year' => $year,
                'country_id' => $countryId,
                'business_id' => $businessId,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting monthly stats with filters', [
                'month' => $month,
                'year' => $year,
                'country_id' => $countryId,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
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
     * Get summary by country and business for a specific date
     */
    public static function getSummaryByFilters(string $date, ?int $countryId = null, ?int $businessId = null): array
    {
        try {
            $query = self::whereDate('date', $date)
                ->with(['employee', 'country', 'business']);

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            if ($businessId) {
                $query->where('business_id', $businessId);
            }

            $attendances = $query->get();

            // Group by country
            $byCountry = [];
            foreach ($attendances->groupBy('country_id') as $countryId => $countryAttendances) {
                $country = $countryAttendances->first()->country ?? null;
                $byCountry[] = [
                    'country_id' => $countryId,
                    'country_name' => $country ? $country->name : 'Unknown',
                    'total_employees' => $countryAttendances->groupBy('employee_id')->count(),
                    'present_count' => $countryAttendances->whereIn('status', ['present', 'completed'])->count(),
                    'absent_count' => $countryAttendances->where('status', 'absent')->count(),
                    'late_count' => $countryAttendances->where('status', 'late')->count(),
                ];
            }

            // Group by business
            $byBusiness = [];
            foreach ($attendances->groupBy('business_id') as $businessId => $businessAttendances) {
                $business = $businessAttendances->first()->business ?? null;
                $byBusiness[] = [
                    'business_id' => $businessId,
                    'business_name' => $business ? $business->name : 'Unknown',
                    'total_employees' => $businessAttendances->groupBy('employee_id')->count(),
                    'present_count' => $businessAttendances->whereIn('status', ['present', 'completed'])->count(),
                    'absent_count' => $businessAttendances->where('status', 'absent')->count(),
                    'late_count' => $businessAttendances->where('status', 'late')->count(),
                ];
            }

            return [
                'by_country' => $byCountry,
                'by_business' => $byBusiness,
                'total_records' => $attendances->count(),
                'total_employees' => $attendances->groupBy('employee_id')->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Error getting summary by filters', [
                'date' => $date,
                'country_id' => $countryId,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
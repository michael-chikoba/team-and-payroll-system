<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
     * Calculate total hours worked
     */
    public function calculateTotalHours(): float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        try {
            // Get the date string
            $dateStr = $this->date instanceof Carbon
                ? $this->date->format('Y-m-d')
                : Carbon::parse($this->date)->format('Y-m-d');

            // Parse clock in time
            $clockIn = $this->parseTime($this->clock_in, $dateStr);

            // Parse clock out time
            $clockOut = $this->parseTime($this->clock_out, $dateStr);

            // Calculate difference in minutes
            $minutes = $clockOut->diffInMinutes($clockIn);

            // Subtract break minutes
            $minutes -= ($this->break_minutes ?? 0);

            // Convert to hours and return (max 0 to prevent negative)
            return max(0, round($minutes / 60, 2));
        } catch (\Exception $e) {
            \Log::error('Error calculating hours', [
                'attendance_id' => $this->id,
                'clock_in' => $this->clock_in,
                'clock_out' => $this->clock_out,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Parse time string to Carbon instance
     */
    private function parseTime(string $time, string $date): Carbon
    {
        // Check if time already has date component
        if (strpos($time, ' ') !== false || strpos($time, 'T') !== false) {
            return Carbon::parse($time);
        }

        // Time only - combine with date
        return Carbon::parse($date . ' ' . $time);
    }

    /**
     * Get overtime hours
     */
    public function getOvertimeHours(): float
    {
        $overtimeThreshold = config('payroll.attendance.overtime_threshold', 8);
        $totalHours = $this->total_hours ?? $this->calculateTotalHours();

        return max(0, round($totalHours - $overtimeThreshold, 2));
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
     * Auto-calculate total hours before saving if not set
     */
    protected static function booted()
    {
        static::saving(function ($attendance) {
            if ($attendance->clock_in && $attendance->clock_out && !$attendance->total_hours) {
                $attendance->total_hours = $attendance->calculateTotalHours();
            }
        });
    }
}
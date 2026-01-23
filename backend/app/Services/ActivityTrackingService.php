<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ActivityTrackingService
{
    private int $idleThreshold;
    private int $checkInterval;

    public function __construct()
    {
        $this->idleThreshold = config('attendance.idle_threshold_minutes', 15);
        $this->checkInterval = config('attendance.idle_check_interval_minutes', 5);
    }

    public function recordHeartbeat(Employee $employee, array $metadata = []): array
    {
        try {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', now()->toDateString())
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->where('is_overtime_session', true)
                ->first();

            if (!$attendance) {
                return [
                    'success' => false,
                    'message' => 'No active overtime session found'
                ];
            }

            $attendance->recordActivity('heartbeat');
            
            $cacheKey = "activity:{$employee->id}:{$attendance->id}";
            Cache::put($cacheKey, [
                'last_heartbeat' => now()->toIso8601String(),
                'metadata' => $metadata,
                'status' => 'active'
            ], now()->addMinutes(30));

            return [
                'success' => true,
                'message' => 'Activity recorded',
                'attendance_id' => $attendance->id,
                'last_activity' => $attendance->last_activity_at?->toIso8601String() ?? now()->toIso8601String(),
                'idle_threshold_minutes' => $this->idleThreshold,
                'current_hours' => round($attendance->calculateTotalHours(), 2)
            ];

        } catch (\Exception $e) {
            Log::error('Heartbeat recording failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record activity',
                'error' => $e->getMessage()
            ];
        }
    }

    public function checkIdleSessions(): array
    {
        $results = [
            'checked' => 0,
            'auto_clocked_out' => 0,
            'still_active' => 0,
            'details' => []
        ];

        try {
            $activeSessions = Attendance::whereNull('clock_out')
                ->where('is_overtime_session', true)
                ->whereDate('date', now()->toDateString())
                ->with(['employee.user'])
                ->get();

            $results['checked'] = $activeSessions->count();

            foreach ($activeSessions as $attendance) {
                // Check if idle - you might need to adjust this based on your isIdle method
                if ($this->isSessionIdle($attendance)) {
                    // Auto clock out
                    $attendance->update([
                        'clock_out' => now(),
                        'auto_clocked_out' => true,
                        'notes' => ($attendance->notes ?? '') . ' Auto-clocked out due to inactivity.'
                    ]);
                    
                    $results['auto_clocked_out']++;
                    $results['details'][] = [
                        'employee_id' => $attendance->employee_id,
                        'employee_name' => optional($attendance->employee)->user->first_name . ' ' . optional($attendance->employee)->user->last_name,
                        'action' => 'auto_clocked_out',
                        'clock_out_time' => now()->toDateTimeString()
                    ];
                    
                    Cache::forget("activity:{$attendance->employee_id}:{$attendance->id}");
                } else {
                    $results['still_active']++;
                }
            }

            Log::info('Idle session check completed', $results);
            return $results;

        } catch (\Exception $e) {
            Log::error('Idle session check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'error' => $e->getMessage(),
                'checked' => 0,
                'auto_clocked_out' => 0,
                'still_active' => 0,
                'details' => []
            ];
        }
    }

    private function isSessionIdle($attendance): bool
    {
        // Check last activity - you need to add this column to your attendance table
        if (!$attendance->last_activity_at) {
            return false; // Or true depending on your logic
        }
        
        $minutesSinceActivity = now()->diffInMinutes($attendance->last_activity_at);
        return $minutesSinceActivity >= $this->idleThreshold;
    }

    public function getActivityStatus(Employee $employee): array
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->where('is_overtime_session', true)
            ->first();

        if (!$attendance) {
            return [
                'has_active_session' => false,
                'status' => 'no_session'
            ];
        }

        $minutesSinceActivity = $attendance->last_activity_at 
            ? now()->diffInMinutes($attendance->last_activity_at)
            : 0;

        $isIdle = $minutesSinceActivity >= $this->idleThreshold;

        return [
            'has_active_session' => true,
            'attendance_id' => $attendance->id,
            'clock_in' => $attendance->clock_in,
            'last_activity_at' => $attendance->last_activity_at?->toIso8601String(),
            'minutes_since_activity' => $minutesSinceActivity,
            'is_idle' => $isIdle,
            'idle_threshold_minutes' => $this->idleThreshold,
            'current_hours' => round($attendance->calculateTotalHours(), 2),
            'status' => $isIdle ? 'idle' : 'active'
        ];
    }
}
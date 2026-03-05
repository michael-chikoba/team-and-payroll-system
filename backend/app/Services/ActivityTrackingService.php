<?php
// app/Services/ActivityTrackingService.php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityTrackingService
{
    private int $idleThresholdMinutes;
    private int $autoCloseAfterWarningMinutes;

    public function __construct()
    {
        $this->idleThresholdMinutes = (int) config('attendance.idle_threshold_minutes', 15);
        $this->autoCloseAfterWarningMinutes = (int) config('attendance.auto_close_after_warning_minutes', 5);
    }

    /**
     * Scan ALL open overtime sessions and apply two-stage idle logic.
     * Called by the scheduler every minute.
     */
    public function checkIdleSessions(): array
    {
        // FIXED: Find ALL open overtime sessions regardless of status or date
        $openOvertimeSessions = Attendance::where('is_overtime_session', 1)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            // Don't filter by status - any open overtime session should be checked
            ->get();

        Log::info('ActivityTracking: Checking idle sessions', [
            'count' => $openOvertimeSessions->count(),
            'session_ids' => $openOvertimeSessions->pluck('id')->toArray(),
            'config' => [
                'idle_threshold' => $this->idleThresholdMinutes,
                'auto_close_after_warning' => $this->autoCloseAfterWarningMinutes,
            ]
        ]);

        $results = [
            'total_checked' => $openOvertimeSessions->count(),
            'warnings_sent' => 0,
            'sessions_closed' => 0,
            'still_active' => 0,
            'pending_close' => 0,
            'details' => [],
        ];

        foreach ($openOvertimeSessions as $session) {
            $outcome = $this->processIdleSession($session);

            match ($outcome['action']) {
                'warned' => $results['warnings_sent']++,
                'closed' => $results['sessions_closed']++,
                'active' => $results['still_active']++,
                'pending_close' => $results['pending_close']++,
                default => null,
            };

            $results['details'][] = $outcome;
        }

        Log::info('ActivityTracking: Idle session check complete', [
            'total_checked' => $results['total_checked'],
            'sessions_closed' => $results['sessions_closed'],
            'warnings_sent' => $results['warnings_sent']
        ]);

        return $results;
    }

    /**
     * Core per-session idle processor.
     */
    private function processIdleSession(Attendance $session): array
    {
        $now = Carbon::now();

        // FIXED: Better handling of last activity time
        if ($session->last_activity_at) {
            $lastActivity = Carbon::parse($session->last_activity_at);
        } else {
            // If no activity recorded, use clock_in time
            $dateOnly = Carbon::parse($session->date)->toDateString();
            $lastActivity = Carbon::parse($dateOnly . ' ' . $session->clock_in);
        }

        $idleMinutes = (float) $lastActivity->diffInMinutes($now, true);

        Log::debug('ActivityTracking: Processing session', [
            'attendance_id' => $session->id,
            'employee_id' => $session->employee_id,
            'date' => $session->date->toDateString(),
            'last_activity' => $lastActivity->toDateTimeString(),
            'idle_minutes' => $idleMinutes,
            'threshold' => $this->idleThresholdMinutes,
            'warning_sent' => $session->idle_warning_sent,
            'warned_at' => $session->idle_warned_at,
        ]);

        // FIXED: For very old sessions (like yesterday), auto-close immediately
        // If session is from a previous day, close it regardless of idle time
        if ($session->date->lt(Carbon::today())) {
            Log::info('ActivityTracking: Closing overtime session from previous day', [
                'attendance_id' => $session->id,
                'session_date' => $session->date->toDateString(),
                'today' => Carbon::today()->toDateString()
            ]);
            
            $this->autoCloseOvertimeSession($session, (int) $idleMinutes, true);
            
            return [
                'attendance_id' => $session->id,
                'employee_id' => $session->employee_id,
                'action' => 'closed',
                'idle_minutes' => round($idleMinutes, 2),
                'message' => 'Session auto-closed because it was from a previous day.',
            ];
        }

        // Not yet idle
        if ($idleMinutes < $this->idleThresholdMinutes) {
            return [
                'attendance_id' => $session->id,
                'employee_id' => $session->employee_id,
                'action' => 'active',
                'idle_minutes' => round($idleMinutes, 2),
                'message' => 'Session is active.',
            ];
        }

        // Idle threshold reached — warning not yet sent
        if (!$session->idle_warning_sent) {
            $session->idle_warning_sent = true;
            $session->idle_warned_at = $now;
            $session->save();

            Log::warning('ActivityTracking: Idle warning issued', [
                'attendance_id' => $session->id,
                'employee_id' => $session->employee_id,
                'idle_minutes' => round($idleMinutes, 2),
            ]);

            return [
                'attendance_id' => $session->id,
                'employee_id' => $session->employee_id,
                'action' => 'warned',
                'idle_minutes' => round($idleMinutes, 2),
                'message' => 'Idle warning sent after ' . round($idleMinutes, 1) . ' minutes of inactivity.',
            ];
        }

        // Warning already sent — check if auto-close window has elapsed
        $warnedAt = Carbon::parse($session->idle_warned_at);
        $secondsSinceWarn = (float) $warnedAt->diffInSeconds($now, true);
        $closeWindowSecs = $this->autoCloseAfterWarningMinutes * 60;

        if ($secondsSinceWarn >= $closeWindowSecs) {
            $this->autoCloseOvertimeSession($session, (int) round($idleMinutes));

            return [
                'attendance_id' => $session->id,
                'employee_id' => $session->employee_id,
                'action' => 'closed',
                'idle_minutes' => round($idleMinutes, 2),
                'minutes_since_warn' => round($secondsSinceWarn / 60, 2),
                'message' => 'Session auto-closed after ' . round($secondsSinceWarn / 60, 1) . ' minutes since warning.',
            ];
        }

        // Warning sent, close window not yet elapsed
        $secondsRemaining = max(0, $closeWindowSecs - $secondsSinceWarn);

        return [
            'attendance_id' => $session->id,
            'employee_id' => $session->employee_id,
            'action' => 'pending_close',
            'idle_minutes' => round($idleMinutes, 2),
            'seconds_remaining' => round($secondsRemaining, 1),
            'message' => 'Warning sent. Auto-close in ~' . round($secondsRemaining) . 's.',
        ];
    }

    /**
     * Auto-close an idle overtime session gracefully.
     */
    private function autoCloseOvertimeSession(Attendance $session, int $idleMinutes, bool $forcePreviousDay = false): void
    {
        // FIXED: Better handling of clock-out time
        if ($forcePreviousDay) {
            // For previous day sessions, clock out at the end of that day (23:59:59)
            $sessionDate = Carbon::parse($session->date);
            $effectiveClockOut = $sessionDate->copy()->endOfDay();
        } else {
            // For current day idle sessions, use last activity time
            $effectiveClockOut = $session->last_activity_at
                ? Carbon::parse($session->last_activity_at)
                : Carbon::now();
        }

        $dateOnly = Carbon::parse($session->date)->toDateString();
        $clockIn = Carbon::parse($dateOnly . ' ' . $session->clock_in);

        $totalMinutes = max(0, $clockIn->diffInMinutes($effectiveClockOut, true));
        $totalHours = round($totalMinutes / 60, 2);

        $session->clock_out = $effectiveClockOut->format('H:i:s');
        $session->idle_warning_sent = false;
        $session->total_hours = $totalHours;
        $session->regular_hours = 0;
        $session->overtime_hours = $totalHours;
        $session->status = 'completed';
        
        if ($forcePreviousDay) {
            $session->notes = trim(
                ($session->notes ? $session->notes . ' | ' : '')
                . "Auto-closed: Session was left open from previous day. "
                . "Clocked out at end of day: {$effectiveClockOut->format('H:i:s')}."
            );
        } else {
            $session->notes = trim(
                ($session->notes ? $session->notes . ' | ' : '')
                . "Auto-closed due to inactivity after {$idleMinutes} minutes. "
                . "Clocked out at last known activity: {$effectiveClockOut->format('H:i:s')}."
            );
        }

        $session->save();

        Log::warning('ActivityTracking: Overtime session auto-closed', [
            'attendance_id' => $session->id,
            'employee_id' => $session->employee_id,
            'session_date' => $session->date->toDateString(),
            'idle_minutes' => $idleMinutes,
            'force_previous_day' => $forcePreviousDay,
            'effective_clock_out' => $effectiveClockOut->toDateTimeString(),
            'total_hours' => $totalHours,
        ]);
    }

    // ... rest of your existing methods (recordHeartbeat, getActivityStatus, etc.) remain the same ...
}
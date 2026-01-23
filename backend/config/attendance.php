<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Shift Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used when an employee doesn't have a shift assigned
    |
    */

    'default_start_time' => env('ATTENDANCE_DEFAULT_START', '08:00'),
    'default_end_time' => env('ATTENDANCE_DEFAULT_END', '16:00'),
    'regular_hours' => env('ATTENDANCE_REGULAR_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Late Arrival Settings
    |--------------------------------------------------------------------------
    |
    | Grace period before marking an employee as late (in minutes)
    |
    */

    'grace_period_minutes' => env('ATTENDANCE_GRACE_PERIOD', 15),

    /*
    |--------------------------------------------------------------------------
    | Auto Clock-Out Settings
    |--------------------------------------------------------------------------
    |
    | Time when system automatically clocks out employees who forgot
    |
    */

    'auto_clockout_time' => env('ATTENDANCE_AUTO_CLOCKOUT', '16:00'),

    /*
    |--------------------------------------------------------------------------
    | Overtime Settings
    |--------------------------------------------------------------------------
    |
    | Settings for overtime calculation
    |
    */

    'overtime_threshold' => env('ATTENDANCE_OVERTIME_THRESHOLD', 8),
    'allow_overtime_sessions' => env('ATTENDANCE_ALLOW_OVERTIME', true),
    'overtime_requires_approval' => env('ATTENDANCE_OVERTIME_APPROVAL', false),
     // Idle detection settings
    'idle_threshold_minutes' => env('ATTENDANCE_IDLE_THRESHOLD', 15),
    'idle_check_interval_minutes' => env('ATTENDANCE_IDLE_CHECK_INTERVAL', 5),
    
    // Heartbeat settings
    'heartbeat_interval_seconds' => env('ATTENDANCE_HEARTBEAT_INTERVAL', 60),
    
    // Regular hours and overtime
    'regular_hours' => env('ATTENDANCE_REGULAR_HOURS', 8),
    'default_start_time' => env('ATTENDANCE_DEFAULT_START', '08:00'),
    'grace_period_minutes' => env('ATTENDANCE_GRACE_PERIOD', 15),
    
    // Activity tracking
    'track_activity' => env('ATTENDANCE_TRACK_ACTIVITY', true),
    'activity_log_max_entries' => env('ATTENDANCE_ACTIVITY_LOG_MAX', 100),

    /*
    |--------------------------------------------------------------------------
    | Break Settings
    |--------------------------------------------------------------------------
    |
    | Default break duration in minutes
    |
    */

    'default_break_minutes' => env('ATTENDANCE_DEFAULT_BREAK', 60),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Application timezone for attendance tracking
    |
    */

    'timezone' => env('ATTENDANCE_TIMEZONE', 'Africa/Lusaka'),

];
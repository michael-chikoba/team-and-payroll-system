<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Grace Period (Minutes)
    |--------------------------------------------------------------------------
    |
    | The number of minutes after the expected start time before an employee
    | is marked as late. For example, if grace_period_minutes is 15, an
    | employee can clock in up to 15 minutes after their shift start time
    | without being marked as late.
    |
    */
    'grace_period_minutes' => env('ATTENDANCE_GRACE_PERIOD', 15),

    /*
    |--------------------------------------------------------------------------
    | Default Start Time
    |--------------------------------------------------------------------------
    |
    | The default expected start time for employees who don't have a shift
    | assigned. This is used as a fallback when determining if someone is late.
    |
    */
    'default_start_time' => env('ATTENDANCE_DEFAULT_START_TIME', '08:30'),

    /*
    |--------------------------------------------------------------------------
    | Auto Clock-Out Time
    |--------------------------------------------------------------------------
    |
    | The time at which the system will automatically clock out employees
    | who forgot to clock out. This prevents attendance records from staying
    | open indefinitely.
    |
    */
    'auto_clock_out_time' => env('ATTENDANCE_AUTO_CLOCK_OUT_TIME', '16:00'),

    /*
    |--------------------------------------------------------------------------
    | Overtime Threshold (Hours)
    |--------------------------------------------------------------------------
    |
    | The number of hours worked before overtime begins. This is used for
    | calculating overtime hours in payroll calculations.
    |
    */
    'overtime_threshold' => env('ATTENDANCE_OVERTIME_THRESHOLD', 8),

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone to use for attendance calculations. This should match
    | your business location.
    |
    */
    'timezone' => env('ATTENDANCE_TIMEZONE', 'Africa/Lusaka'),

    /*
    |--------------------------------------------------------------------------
    | Shift-Based Attendance
    |--------------------------------------------------------------------------
    |
    | Enable or disable shift-based attendance tracking. When enabled, the
    | system will use assigned shifts to determine expected times and late
    | status. When disabled, it uses default_start_time.
    |
    */
    'shift_based_tracking' => env('ATTENDANCE_SHIFT_BASED', true),

    /*
    |--------------------------------------------------------------------------
    | Late Penalties
    |--------------------------------------------------------------------------
    |
    | Configure different late thresholds and their severity levels.
    | This can be used for reporting or payroll deductions.
    |
    */
    'late_thresholds' => [
        'minor' => 15,      // Up to 15 minutes
        'moderate' => 30,   // 15-30 minutes
        'major' => 60,      // 30-60 minutes
        'severe' => 999,    // Over 60 minutes
    ],
];
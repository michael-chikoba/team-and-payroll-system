<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified'        => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'superadmin'      => \App\Http\Middleware\IsSuperAdmin::class,
            'role'            => \App\Http\Middleware\CheckRole::class,
            'business_group'  => \App\Http\Middleware\CheckBusinessGroupAccess::class,
            'json-api'        => \App\Http\Middleware\ValidateJsonApiDocument::class,
            'ensure.employee' => \App\Http\Middleware\EnsureUserHasEmployee::class,
            'dept'            => \App\Http\Middleware\ValidateDepartment::class,
            'account.active'  => \App\Http\Middleware\CheckAccountStatus::class,
            \App\Http\Middleware\CheckTokenExpiration::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Check for idle sessions every minute (not 5 minutes)
        // Idle threshold is 15 minutes + 5 minute warning = 20 minutes total
        // Running every minute ensures timely warnings and auto-closing
        $schedule->command('sessions:check-idle')
            ->everyMinute()
            ->name('check-idle-overtime-sessions')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/idle-sessions.log'));

        // Clean up any missed sessions from previous days at 12:05 AM daily
        $schedule->command('attendance:fix-previous-day-overtime')
            ->dailyAt('00:05')
            ->name('fix-previous-day-overtime')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/overtime-cleanup.log'));

        // Optional: Add a heartbeat check every 5 minutes to ensure system is working
        $schedule->call(function () {
            \Illuminate\Support\Facades\Log::info('Scheduler heartbeat - idle check should be running every minute');
        })->everyFiveMinutes()->name('scheduler-heartbeat');
    })
    ->create();
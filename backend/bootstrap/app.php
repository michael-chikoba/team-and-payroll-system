<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // Import this

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
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'json-api' => \App\Http\Middleware\ValidateJsonApiDocument::class,
            'ensure.employee' => \App\Http\Middleware\EnsureUserHasEmployee::class,
            'dept' => \App\Http\Middleware\ValidateDepartment::class,
            \App\Http\Middleware\CheckTokenExpiration::class,

        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
         // ADD THIS LINE TO TRUST PROXIES
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
         })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Check for idle sessions every 5 minutes
        $schedule->command('sessions:check-idle')
            ->everyFiveMinutes()
            ->name('check-idle-overtime-sessions')
            ->withoutOverlapping()
            ->runInBackground();
    })->create();
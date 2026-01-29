<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use App\Listeners\LogUserLogin;
use App\Services\TicketNotificationService;
use App\Services\SlackService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SlackService as singleton first
        $this->app->singleton(SlackService::class, function ($app) {
            return new SlackService();
        });

        // Register TicketNotificationService as singleton with dependency injection
        $this->app->singleton(TicketNotificationService::class, function ($app) {
            return new TicketNotificationService($app->make(SlackService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
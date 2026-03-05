<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use App\Listeners\LogUserLogin;
use App\Services\TicketNotificationService;
use App\Services\SlackService;
use Illuminate\Support\Collection;
use App\Services\EncryptionService;

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
// Register as singleton so the service is shared across the request lifecycle
        $this->app->singleton(EncryptionService::class, function () {
            return new EncryptionService();
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
            Collection::macro('safeSum', function($callback) {
        return $this->sum(function($item) use ($callback) {
            $value = is_callable($callback) ? $callback($item) : $item->{$callback};
            return (float) ($value ?? 0);
        });
    });
    }
}
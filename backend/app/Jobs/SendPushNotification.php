<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public UserNotification $notification
    ) {
        $this->onQueue(config('push-notifications.queue.queue', 'push-notifications'));
    }

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        try {
            Log::info('Processing push notification job', [
                'user_id' => $this->user->id,
                'notification_id' => $this->notification->id,
                'type' => $this->notification->type
            ]);

            $results = $pushService->sendToUser($this->user, $this->notification);

            Log::info('Push notification job completed', [
                'user_id' => $this->user->id,
                'notification_id' => $this->notification->id,
                'results_count' => count($results),
                'successful' => collect($results)->where('success', true)->count(),
                'failed' => collect($results)->where('success', false)->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Push notification job failed', [
                'user_id' => $this->user->id,
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job permanently failed', [
            'user_id' => $this->user->id,
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage()
        ]);
    }
}
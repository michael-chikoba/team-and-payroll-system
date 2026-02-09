<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\UserNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('push-notifications.vapid.subject'),
                'publicKey' => config('push-notifications.vapid.public_key'),
                'privateKey' => config('push-notifications.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Send push notification to a specific user
     */
    public function sendToUser(User $user, UserNotification $notification): array
    {
        $results = [];
        
        // Check if user has push enabled
        $preferences = $this->getUserPreferences($user);
        
        if (!$this->shouldSendPush($preferences, $notification)) {
            Log::info('Push notification skipped due to preferences', [
                'user_id' => $user->id,
                'notification_id' => $notification->id
            ]);
            return $results;
        }

        // Get all active subscriptions for the user
        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->active()
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::info('No active push subscriptions found', ['user_id' => $user->id]);
            return $results;
        }

        // Prepare notification payload
        $payload = $this->preparePayload($notification);

        // Send to each subscription
        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->sendToSubscription($subscription, $payload);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error('Failed to send push notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
                $results[] = [
                    'subscription_id' => $subscription->id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToMultipleUsers(array $userIds, UserNotification $notification): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $results[$userId] = $this->sendToUser($user, $notification);
            }
        }

        return $results;
    }

    /**
     * Send push notification to a specific subscription
     */
    private function sendToSubscription(PushSubscription $subscription, array $payload): array
    {
        $subscriptionData = Subscription::create([
            'endpoint' => $subscription->endpoint,
            'keys' => [
                'p256dh' => $subscription->public_key,
                'auth' => $subscription->auth_token
            ],
            'contentEncoding' => $subscription->content_encoding ?? 'aesgcm'
        ]);

        $report = $this->webPush->sendOneNotification(
            $subscriptionData,
            json_encode($payload),
            [
                'TTL' => config('push-notifications.ttl'),
                'urgency' => config('push-notifications.defaults.urgency'),
                'topic' => config('push-notifications.defaults.topic'),
            ]
        );

        // Handle the response
        if ($report->isSuccess()) {
            $subscription->markAsUsed();
            
            Log::info('Push notification sent successfully', [
                'subscription_id' => $subscription->id,
                'endpoint' => $subscription->endpoint
            ]);

            return [
                'subscription_id' => $subscription->id,
                'success' => true
            ];
        } else {
            // Handle different error cases
            if ($report->isSubscriptionExpired()) {
                $subscription->deactivate();
                Log::warning('Push subscription expired', [
                    'subscription_id' => $subscription->id
                ]);
            }

            Log::error('Push notification failed', [
                'subscription_id' => $subscription->id,
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired()
            ]);

            return [
                'subscription_id' => $subscription->id,
                'success' => false,
                'reason' => $report->getReason(),
                'expired' => $report->isSubscriptionExpired()
            ];
        }
    }

    /**
     * Prepare notification payload
     */
    private function preparePayload(UserNotification $notification): array
    {
        $typeConfig = config("push-notifications.types.{$notification->type}", []);
        
        $payload = [
            'title' => $notification->title ?? $typeConfig['title'] ?? 'Notification',
            'body' => $notification->message,
            'icon' => $typeConfig['icon'] ?? '/images/icons/default.png',
            'badge' => $typeConfig['badge'] ?? '/images/icons/badge.png',
            'data' => [
                'id' => $notification->id,
                'type' => $notification->type,
                'action' => $notification->action,
                'url' => $this->getNotificationUrl($notification),
                'timestamp' => now()->timestamp,
                ...$notification->data ?? []
            ],
            'requireInteraction' => $typeConfig['requires_action'] ?? false,
        ];

        // Add actions if defined
        if (isset($typeConfig['actions'])) {
            $payload['actions'] = $typeConfig['actions'];
        }

        return $payload;
    }

    /**
     * Get notification URL based on type and action
     */
    private function getNotificationUrl(UserNotification $notification): string
    {
        if ($notification->action) {
            return $notification->action;
        }

        // Default URLs based on notification type
        $urlMap = [
            'business_group_invitation' => '/invitations',
            'task_assigned' => '/tasks',
            'schedule_updated' => '/schedule',
            'leave_request' => '/leave-requests',
            'ticket_created' => '/tickets',
            'reminder' => '/dashboard',
            'system_announcement' => '/announcements',
        ];

        return $urlMap[$notification->type] ?? '/notifications';
    }

    /**
     * Check if push notification should be sent based on preferences
     */
    private function shouldSendPush(NotificationPreference $preferences, UserNotification $notification): bool
    {
        // Check if push is globally enabled
        if (!$preferences->push_enabled) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isInQuietHours()) {
            return false;
        }

        // Check specific notification type preference
        return $preferences->isPushEnabledFor($notification->type);
    }

    /**
     * Get user preferences (create if not exists)
     */
    private function getUserPreferences(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            NotificationPreference::getDefaults()
        );
    }

    /**
     * Test push notification
     */
    public function sendTestNotification(User $user): array
    {
        $testNotification = new UserNotification([
            'user_id' => $user->id,
            'type' => 'system_announcement',
            'title' => 'Test Notification',
            'message' => 'This is a test push notification. If you see this, push notifications are working!',
            'action' => '/notifications',
            'data' => ['test' => true]
        ]);

        return $this->sendToUser($user, $testNotification);
    }

    /**
     * Flush the web push queue
     */
    public function flush(): array
    {
        return $this->webPush->flush();
    }
}
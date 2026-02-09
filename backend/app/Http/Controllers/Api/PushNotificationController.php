<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\NotificationPreference;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
                'device_type' => 'nullable|string|in:web,mobile,tablet',
                'browser' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $data = $validator->validated();

            // Create or update subscription
            $subscription = PushSubscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'endpoint' => $data['endpoint']
                ],
                [
                    'public_key' => $data['keys']['p256dh'],
                    'auth_token' => $data['keys']['auth'],
                    'device_type' => $data['device_type'] ?? 'web',
                    'browser' => $data['browser'] ?? null,
                    'is_active' => true,
                    'last_used_at' => now()
                ]
            );

            Log::info('Push subscription created/updated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'endpoint' => substr($subscription->endpoint, 0, 50) . '...'
            ]);

            return response()->json([
                'message' => 'Successfully subscribed to push notifications',
                'subscription' => [
                    'id' => $subscription->id,
                    'device_type' => $subscription->device_type,
                    'created_at' => $subscription->created_at
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create push subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to subscribe to push notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $endpoint = $request->input('endpoint');

            $subscription = PushSubscription::where('user_id', $user->id)
                ->where('endpoint', $endpoint)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'message' => 'Subscription not found'
                ], 404);
            }

            $subscription->delete();

            Log::info('Push subscription deleted', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'message' => 'Successfully unsubscribed from push notifications'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete push subscription', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to unsubscribe from push notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's notification preferences
     */
    public function getPreferences()
    {
        try {
            $user = Auth::user();
            
            $preferences = NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                NotificationPreference::getDefaults()
            );

            return response()->json([
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notification preferences', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's notification preferences
     */
    public function updatePreferences(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'push_enabled' => 'nullable|boolean',
                'email_enabled' => 'nullable|boolean',
                'push_business_group_invitation' => 'nullable|boolean',
                'push_task_assigned' => 'nullable|boolean',
                'push_schedule_updated' => 'nullable|boolean',
                'push_leave_request' => 'nullable|boolean',
                'push_ticket_created' => 'nullable|boolean',
                'push_reminder' => 'nullable|boolean',
                'push_system_announcement' => 'nullable|boolean',
                'email_business_group_invitation' => 'nullable|boolean',
                'email_task_assigned' => 'nullable|boolean',
                'email_schedule_updated' => 'nullable|boolean',
                'email_leave_request' => 'nullable|boolean',
                'email_ticket_created' => 'nullable|boolean',
                'email_reminder' => 'nullable|boolean',
                'email_system_announcement' => 'nullable|boolean',
                'quiet_hours_enabled' => 'nullable|boolean',
                'quiet_hours_start' => 'nullable|date_format:H:i',
                'quiet_hours_end' => 'nullable|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            $preferences = NotificationPreference::updateOrCreate(
                ['user_id' => $user->id],
                $validator->validated()
            );

            Log::info('Notification preferences updated', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Notification preferences updated successfully',
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a test push notification
     */
    public function test()
    {
        try {
            $user = Auth::user();
            
            $results = $this->pushService->sendTestNotification($user);

            if (empty($results)) {
                return response()->json([
                    'message' => 'No active push subscriptions found. Please subscribe to push notifications first.',
                    'results' => []
                ], 404);
            }

            $successCount = collect($results)->where('success', true)->count();
            $failCount = collect($results)->where('success', false)->count();

            Log::info('Test push notification sent', [
                'user_id' => $user->id,
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);

            return response()->json([
                'message' => 'Test notification sent',
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's push subscriptions
     */
    public function getSubscriptions()
    {
        try {
            $user = Auth::user();
            
            $subscriptions = PushSubscription::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'device_type' => $sub->device_type,
                        'browser' => $sub->browser,
                        'is_active' => $sub->is_active,
                        'last_used_at' => $sub->last_used_at,
                        'created_at' => $sub->created_at
                    ];
                });

            return response()->json([
                'subscriptions' => $subscriptions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch push subscriptions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch push subscriptions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
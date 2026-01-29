<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Models\ChatGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NotificationChannelController extends Controller
{
    /**
     * Get all notification channels for the business
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee || !$employee->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not assigned to any business'
                ], 403);
            }

            $channels = NotificationChannel::forBusiness($employee->business_id)
                ->with(['chatGroup', 'creator'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($channel) {
                    return [
                        'id' => $channel->id,
                        'name' => $channel->name,
                        'channel_type' => $channel->channel_type,
                        'channel_type_display' => $channel->getChannelTypeDisplayName(),
                        'destination' => $channel->getDestination(),
                        'subscribed_events' => $channel->subscribed_events ?? [],
                        'filters' => $channel->filters ?? [],
                        'is_active' => $channel->is_active,
                        'created_by' => $channel->creator->name ?? 'Unknown',
                        'created_at' => $channel->created_at,
                        'chat_group' => $channel->chatGroup ? [
                            'id' => $channel->chatGroup->id,
                            'name' => $channel->chatGroup->name,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'channels' => $channels,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notification channels', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification channels',
            ], 500);
        }
    }

    /**
     * Create a new notification channel
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can create notification channels'
            ], 403);
        }

        $employee = $user->employee;

        if (!$employee || !$employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not assigned to any business'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'channel_type' => 'required|in:chat_group,email,slack,webhook',
            'chat_group_id' => 'required_if:channel_type,chat_group|exists:chat_groups,id',
            'email_address' => 'required_if:channel_type,email|email',
            'slack_webhook_url' => 'required_if:channel_type,slack|url',
            'webhook_url' => 'required_if:channel_type,webhook|url',
            'subscribed_events' => 'required|array|min:1',
            'subscribed_events.*' => 'string',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify chat group belongs to same business
            if ($request->channel_type === 'chat_group') {
                $chatGroup = ChatGroup::find($request->chat_group_id);
                if (!$chatGroup || $chatGroup->business_id !== $employee->business_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chat group must belong to your business'
                    ], 422);
                }
            }

            $channel = NotificationChannel::create([
                'business_id' => $employee->business_id,
                'name' => $request->name,
                'channel_type' => $request->channel_type,
                'chat_group_id' => $request->chat_group_id,
                'email_address' => $request->email_address,
                'slack_webhook_url' => $request->slack_webhook_url,
                'webhook_url' => $request->webhook_url,
                'subscribed_events' => $request->subscribed_events,
                'filters' => $request->filters ?? [],
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            $channel->load(['chatGroup', 'creator']);

            Log::info('Notification channel created', [
                'channel_id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->channel_type,
                'business_id' => $employee->business_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification channel created successfully',
                'channel' => $channel,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create notification channel', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification channel',
            ], 500);
        }
    }

    /**
     * Update a notification channel
     */
    public function update(Request $request, NotificationChannel $channel)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update notification channels'
            ], 403);
        }

        $employee = $user->employee;

        if (!$employee || $channel->business_id !== $employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'subscribed_events' => 'sometimes|array|min:1',
            'subscribed_events.*' => 'string',
            'filters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $channel->update($request->only([
                'name',
                'subscribed_events',
                'filters',
                'is_active',
            ]));

            Log::info('Notification channel updated', [
                'channel_id' => $channel->id,
                'name' => $channel->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification channel updated successfully',
                'channel' => $channel->fresh(['chatGroup', 'creator']),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification channel', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification channel',
            ], 500);
        }
    }

    /**
     * Delete a notification channel
     */
    public function destroy(NotificationChannel $channel)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete notification channels'
            ], 403);
        }

        $employee = $user->employee;

        if (!$employee || $channel->business_id !== $employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $channel->delete();

            Log::info('Notification channel deleted', [
                'channel_id' => $channel->id,
                'name' => $channel->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification channel deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete notification channel', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification channel',
            ], 500);
        }
    }

    /**
     * Get available chat groups for notifications
     */
    public function getAvailableChatGroups(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee || !$employee->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not assigned to any business'
                ], 403);
            }

            $chatGroups = ChatGroup::forBusiness($employee->business_id)
                ->where('is_channel', true) // Only channels, not DMs
                ->where('is_archived', false)
                ->orderBy('name')
                ->get()
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'display_name' => $group->getDisplayName(),
                        'description' => $group->description,
                        'is_private' => $group->is_private,
                        'member_count' => $group->members()->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'chat_groups' => $chatGroups,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch available chat groups', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch chat groups',
            ], 500);
        }
    }

    /**
     * Get available events
     */
    public function getAvailableEvents()
    {
        return response()->json([
            'success' => true,
            'events' => NotificationChannel::getAvailableEvents(),
        ]);
    }

    /**
     * Test a notification channel
     */
    public function test(NotificationChannel $channel)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can test notification channels'
            ], 403);
        }

        $employee = $user->employee;

        if (!$employee || $channel->business_id !== $employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $testData = [
                'title' => '🧪 Test Notification',
                'message' => "This is a test notification from the HelpDesk system.\n\n" .
                           "**Channel:** {$channel->name}\n" .
                           "**Type:** {$channel->getChannelTypeDisplayName()}\n" .
                           "**Sent at:** " . now()->format('Y-m-d H:i:s'),
                'ticket' => [
                    'id' => 0,
                    'title' => 'Test Ticket',
                ],
            ];

            $service = app(\App\Services\TicketNotificationService::class);
            
            switch ($channel->channel_type) {
                case 'chat_group':
                    $service->sendToChatGroup($channel, $testData);
                    break;
                case 'email':
                    $service->sendToEmail($channel, $testData);
                    break;
                case 'slack':
                    $service->sendToSlack($channel, $testData);
                    break;
                case 'webhook':
                    $service->sendToWebhook($channel, $testData);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification logs
     */
    public function getLogs(Request $request, NotificationChannel $channel)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee || $channel->business_id !== $employee->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $logs = $channel->logs()
                ->with('ticket')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notification logs', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logs',
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatIntegration;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatWebhookController extends Controller
{
    /**
     * Send a message via webhook (public API)
     * 
     * External applications use this endpoint
     */
    public function sendMessage(Request $request): JsonResponse
    {
        // Get API key from header
        $apiKey = $request->header('X-Chat-Api-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Missing API key. Provide X-Chat-Api-Key header.',
            ], 401);
        }

        // Find integration by API key
        $integration = ChatIntegration::where('api_key', $apiKey)
            ->active()
            ->with('chatGroup')
            ->first();

        if (!$integration) {
            Log::warning('Invalid API key used', [
                'api_key_prefix' => substr($apiKey, 0, 10),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
            ], 401);
        }

        // Rate limiting
        $rateLimitKey = 'chat-webhook:' . $integration->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 100)) {
            $integration->logActivity(
                'message_sent',
                'failed',
                null,
                'Rate limit exceeded'
            );

            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded. Maximum 100 messages per hour.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600); // 1 hour

        // Verify webhook signature if secret is set
        $signature = $request->header('X-Chat-Signature');
        $rawPayload = $request->getContent();

        if (!$integration->verifyWebhookSignature($signature, $rawPayload)) {
            $integration->logActivity(
                'message_sent',
                'failed',
                $rawPayload,
                'Invalid webhook signature'
            );

            return response()->json([
                'success' => false,
                'error' => 'Invalid webhook signature',
            ], 401);
        }

        // Validate request
        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*.type' => 'required|in:image,file,link',
            'attachments.*.url' => 'required|url',
            'attachments.*.title' => 'nullable|string|max:255',
            'attachments.*.description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        try {
            // Check if integration can send messages
            if (!$integration->canSendMessages()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Integration is disabled or rate limited',
                ], 403);
            }

            // Create the message
            $messageData = [
                'chat_group_id' => $integration->chat_group_id,
                'user_id' => $integration->created_by, // Bot messages appear from creator
                'message' => $validated['message'],
                'type' => 'integration',
                'metadata' => array_merge(
                    $validated['metadata'] ?? [],
                    [
                        'integration_id' => $integration->id,
                        'integration_name' => $integration->name,
                        'icon_url' => $integration->icon_url,
                        'attachments' => $validated['attachments'] ?? [],
                    ]
                ),
            ];

            $message = ChatMessage::create($messageData);

            // Update integration usage
            $integration->incrementUsage();

            // Log successful activity
            $integration->logActivity(
                'message_sent',
                'success',
                json_encode([
                    'message_id' => $message->id,
                    'message_preview' => substr($validated['message'], 0, 100),
                ])
            );

            Log::info('Webhook message sent', [
                'integration_id' => $integration->id,
                'message_id' => $message->id,
                'group_id' => $integration->chat_group_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'timestamp' => $message->created_at,
                    'channel' => $integration->chatGroup->name,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send webhook message', [
                'error' => $e->getMessage(),
                'integration_id' => $integration->id,
            ]);

            $integration->logActivity(
                'message_sent',
                'failed',
                json_encode($validated),
                $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'error' => 'Failed to send message',
            ], 500);
        }
    }

    /**
     * Get integration info (for external apps to verify their key)
     */
    public function getInfo(Request $request): JsonResponse
    {
        $apiKey = $request->header('X-Chat-Api-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Missing API key',
            ], 401);
        }

        $integration = ChatIntegration::where('api_key', $apiKey)
            ->active()
            ->with('chatGroup')
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'integration' => [
                'name' => $integration->name,
                'channel' => $integration->chatGroup->name,
                'permissions' => $integration->permissions,
                'is_active' => $integration->is_active,
                'message_count' => $integration->message_count,
                'last_used' => $integration->last_used_at,
            ],
        ]);
    }
}
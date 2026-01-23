<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ChatIntegrationController extends Controller
{
    /**
     * Get all integrations for a channel/group
     */
    public function index(Request $request, int $groupId): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            // Check if user is admin of the group
            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can view integrations'
                ], 403);
            }

            $integrations = ChatIntegration::forGroup($groupId)
                ->with('creator')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($integration) {
                    return [
                        'id' => $integration->id,
                        'name' => $integration->name,
                        'description' => $integration->description,
                        'icon_url' => $integration->icon_url,
                        'is_active' => $integration->is_active,
                        'message_count' => $integration->message_count,
                        'last_used_at' => $integration->last_used_at,
                        'created_by' => [
                            'id' => $integration->creator->id,
                            'name' => $integration->creator->name,
                        ],
                        'created_at' => $integration->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'integrations' => $integrations,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integrations', [
                'error' => $e->getMessage(),
                'group_id' => $groupId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch integrations'
            ], 500);
        }
    }

    /**
     * Create a new integration
     */
    public function store(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon_url' => 'nullable|url|max:500',
            'permissions' => 'nullable|array',
            'generate_webhook_secret' => 'boolean',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            // Check if user is admin
            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can create integrations'
                ], 403);
            }

            $integration = ChatIntegration::create([
                'chat_group_id' => $groupId,
                'created_by' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'api_key' => ChatIntegration::generateApiKey(),
                'webhook_secret' => ($validated['generate_webhook_secret'] ?? false) 
                    ? ChatIntegration::generateWebhookSecret() 
                    : null,
                'icon_url' => $validated['icon_url'] ?? null,
                'permissions' => $validated['permissions'] ?? ['send_messages'],
                'is_active' => true,
            ]);

            Log::info('Chat integration created', [
                'integration_id' => $integration->id,
                'group_id' => $groupId,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration created successfully',
                'integration' => [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'description' => $integration->description,
                    'api_key' => $integration->api_key, // Only shown once during creation
                    'webhook_secret' => $integration->webhook_secret,
                    'icon_url' => $integration->icon_url,
                    'is_active' => $integration->is_active,
                    'created_at' => $integration->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create integration', [
                'error' => $e->getMessage(),
                'group_id' => $groupId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create integration'
            ], 500);
        }
    }

    /**
     * Update an integration
     */
    public function update(Request $request, int $groupId, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);
            $integration = ChatIntegration::where('chat_group_id', $groupId)
                ->findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can update integrations'
                ], 403);
            }

            $integration->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Integration updated successfully',
                'integration' => [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'description' => $integration->description,
                    'icon_url' => $integration->icon_url,
                    'is_active' => $integration->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update integration', [
                'error' => $e->getMessage(),
                'integration_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update integration'
            ], 500);
        }
    }

    /**
     * Delete an integration
     */
    public function destroy(Request $request, int $groupId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);
            $integration = ChatIntegration::where('chat_group_id', $groupId)
                ->findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can delete integrations'
                ], 403);
            }

            $integration->delete();

            Log::info('Chat integration deleted', [
                'integration_id' => $id,
                'group_id' => $groupId,
                'deleted_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete integration', [
                'error' => $e->getMessage(),
                'integration_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete integration'
            ], 500);
        }
    }

    /**
     * Regenerate API key
     */
    public function regenerateApiKey(Request $request, int $groupId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);
            $integration = ChatIntegration::where('chat_group_id', $groupId)
                ->findOrFail($id);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can regenerate API keys'
                ], 403);
            }

            $newApiKey = ChatIntegration::generateApiKey();
            $integration->update(['api_key' => $newApiKey]);

            $integration->logActivity(
                'api_key_regenerated',
                'success',
                json_encode(['regenerated_by' => $user->id])
            );

            return response()->json([
                'success' => true,
                'message' => 'API key regenerated successfully',
                'api_key' => $newApiKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate API key', [
                'error' => $e->getMessage(),
                'integration_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate API key'
            ], 500);
        }
    }

    /**
     * Get integration logs
     */
    public function logs(Request $request, int $groupId, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can view integration logs'
                ], 403);
            }

            $integration = ChatIntegration::where('chat_group_id', $groupId)
                ->findOrFail($id);

            $logs = $integration->logs()
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integration logs', [
                'error' => $e->getMessage(),
                'integration_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch logs'
            ], 500);
        }
    }
}
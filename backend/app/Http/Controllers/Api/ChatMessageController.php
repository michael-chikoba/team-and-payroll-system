<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatMessageController extends Controller
{
    /**
     * Get messages for a group
     */
    public function index(Request $request, int $groupId): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $perPage = $request->query('per_page', 50);
            $before = $request->query('before'); // Message ID to fetch messages before

            $query = ChatMessage::where('chat_group_id', $groupId)
                ->with(['user', 'replyTo.user'])
                ->notDeleted()
                ->orderByDesc('created_at');

            if ($before) {
                $beforeMessage = ChatMessage::find($before);
                if ($beforeMessage) {
                    $query->where('created_at', '<', $beforeMessage->created_at);
                }
            }

            $messages = $query->limit($perPage)->get()->reverse()->values();

            // Mark as read
            $group->markAsRead($user->id);

            return response()->json([
                'success' => true,
                'messages' => $messages->map(function($msg) use ($user) {
                    return [
                        'id' => $msg->id,
                        'user' => [
                            'id' => $msg->user->id,
                            'name' => $msg->user->name,
                            'avatar' => $msg->user->avatar ?? null,
                        ],
                        'message' => $msg->message,
                        'type' => $msg->type,
                        'attachment_url' => $msg->attachment_url,
                        'attachment_name' => $msg->attachment_name,
                        'attachment_size' => $msg->attachment_size,
                        'reply_to' => $msg->replyTo ? [
                            'id' => $msg->replyTo->id,
                            'user_name' => $msg->replyTo->user->name,
                            'message' => \Illuminate\Support\Str::limit($msg->replyTo->message, 50),
                        ] : null,
                        'is_edited' => $msg->is_edited,
                        'edited_at' => $msg->edited_at,
                        'is_own' => $msg->user_id === $user->id,
                        'created_at' => $msg->created_at->toISOString(),
                        'formatted_time' => $msg->formatted_time,
                    ];
                }),
                'has_more' => $messages->count() === $perPage
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch messages', [
                'error' => $e->getMessage(),
                'group_id' => $groupId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function store(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required_without:attachment|string|max:10000',
            'type' => 'sometimes|in:text,file,image',
            'attachment' => 'sometimes|file|max:10240', // 10MB max
            'reply_to_message_id' => 'nullable|exists:chat_messages,id',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $messageData = [
                'chat_group_id' => $groupId,
                'user_id' => $user->id,
                'message' => $validated['message'] ?? '',
                'type' => $validated['type'] ?? 'text',
                'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
            ];

            // Handle file attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('chat-attachments', 'public');
                
                $messageData['attachment_url'] = Storage::url($path);
                $messageData['attachment_name'] = $file->getClientOriginalName();
                $messageData['attachment_size'] = $file->getSize();
                $messageData['type'] = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
                
                if (empty($messageData['message'])) {
                    $messageData['message'] = '📎 ' . $file->getClientOriginalName();
                }
            }

            $message = ChatMessage::create($messageData);
            $message->load(['user', 'replyTo.user']);

            // Update group's updated_at to move it to top
            $group->touch();

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->avatar ?? null,
                    ],
                    'message' => $message->message,
                    'type' => $message->type,
                    'attachment_url' => $message->attachment_url,
                    'attachment_name' => $message->attachment_name,
                    'attachment_size' => $message->attachment_size,
                    'reply_to' => $message->replyTo ? [
                        'id' => $message->replyTo->id,
                        'user_name' => $message->replyTo->user->name,
                        'message' => \Illuminate\Support\Str::limit($message->replyTo->message, 50),
                    ] : null,
                    'is_edited' => false,
                    'is_own' => true,
                    'created_at' => $message->created_at->toISOString(),
                    'formatted_time' => $message->formatted_time,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'group_id' => $groupId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit a message
     */
    public function update(Request $request, int $groupId, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        try {
            $user = $request->user();
            $message = ChatMessage::where('chat_group_id', $groupId)
                ->where('id', $messageId)
                ->firstOrFail();

            if ($message->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only edit your own messages'
                ], 403);
            }

            if ($message->type !== 'text') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only edit text messages'
                ], 400);
            }

            $message->update([
                'message' => $validated['message'],
                'is_edited' => true,
                'edited_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message updated successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to edit message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to edit message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function destroy(Request $request, int $groupId, int $messageId): JsonResponse
    {
        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);
            $message = ChatMessage::where('chat_group_id', $groupId)
                ->where('id', $messageId)
                ->firstOrFail();

            // User can delete own message, or admin can delete any
            if ($message->user_id !== $user->id && !$group->isAdmin($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own messages'
                ], 403);
            }

            $message->update([
                'is_deleted' => true,
                'message' => 'This message was deleted',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search messages in a group
     */
    public function search(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
        ]);

        try {
            $user = $request->user();
            $group = ChatGroup::findOrFail($groupId);

            if (!$group->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group'
                ], 403);
            }

            $messages = ChatMessage::where('chat_group_id', $groupId)
                ->where('message', 'like', "%{$validated['query']}%")
                ->with(['user'])
                ->notDeleted()
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function($msg) use ($user) {
                    return [
                        'id' => $msg->id,
                        'user_name' => $msg->user->name,
                        'message' => $msg->message,
                        'type' => $msg->type,
                        'created_at' => $msg->created_at->toISOString(),
                        'formatted_time' => $msg->formatted_time,
                    ];
                });

            return response()->json([
                'success' => true,
                'results' => $messages,
                'count' => $messages->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to search messages', [
                'error' => $e->getMessage(),
                'group_id' => $groupId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
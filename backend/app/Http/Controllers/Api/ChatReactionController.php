<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\ChatThread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// ========================================================
// CHAT REACTION CONTROLLER
// ========================================================

class ChatReactionController extends Controller
{
    /**
     * Toggle reaction on a message
     */
    public function toggle(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:50',
        ]);

        try {
            $user = $request->user();
            $message = ChatMessage::findOrFail($messageId);
            
            // Check if user has access to this message's group
            if (!$message->chatGroup->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this message'
                ], 403);
            }

            $existing = ChatReaction::where('chat_message_id', $messageId)
                ->where('user_id', $user->id)
                ->where('emoji', $validated['emoji'])
                ->first();

            if ($existing) {
                // Remove reaction
                $existing->delete();
                $action = 'removed';
            } else {
                // Add reaction
                ChatReaction::create([
                    'chat_message_id' => $messageId,
                    'user_id' => $user->id,
                    'emoji' => $validated['emoji'],
                ]);
                $action = 'added';
            }

            // Get updated reactions count
            $reactions = ChatReaction::where('chat_message_id', $messageId)
                ->select('emoji', DB::raw('count(*) as count'))
                ->groupBy('emoji')
                ->get()
                ->map(function($reaction) use ($messageId) {
                    $users = ChatReaction::where('chat_message_id', $messageId)
                        ->where('emoji', $reaction->emoji)
                        ->with('user:id,name')
                        ->get()
                        ->pluck('user.name')
                        ->toArray();
                    
                    return [
                        'emoji' => $reaction->emoji,
                        'count' => $reaction->count,
                        'users' => $users,
                    ];
                });

            return response()->json([
                'success' => true,
                'action' => $action,
                'reactions' => $reactions,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle reaction', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle reaction',
            ], 500);
        }
    }

    /**
     * Get all reactions for a message
     */
    public function index(int $messageId): JsonResponse
    {
        try {
            $reactions = ChatReaction::where('chat_message_id', $messageId)
                ->with('user:id,name,avatar')
                ->get()
                ->groupBy('emoji')
                ->map(function($group, $emoji) {
                    return [
                        'emoji' => $emoji,
                        'count' => $group->count(),
                        'users' => $group->map(function($reaction) {
                            return [
                                'id' => $reaction->user->id,
                                'name' => $reaction->user->name,
                                'avatar' => $reaction->user->avatar,
                            ];
                        })->values(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'reactions' => $reactions,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get reactions', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get reactions',
            ], 500);
        }
    }
}

// ========================================================
// CHAT THREAD CONTROLLER
// ========================================================

class ChatThreadController extends Controller
{
    /**
     * Create a thread from a message
     */
    public function createFromMessage(Request $request, int $messageId): JsonResponse
    {
        try {
            $user = $request->user();
            $message = ChatMessage::with('chatGroup')->findOrFail($messageId);

            if (!$message->chatGroup->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this message'
                ], 403);
            }

            // Check if thread already exists
            $thread = ChatThread::where('parent_message_id', $messageId)->first();

            if (!$thread) {
                $thread = ChatThread::create([
                    'chat_group_id' => $message->chat_group_id,
                    'parent_message_id' => $messageId,
                    'reply_count' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'thread' => [
                    'id' => $thread->id,
                    'parent_message_id' => $thread->parent_message_id,
                    'reply_count' => $thread->reply_count,
                    'last_reply_at' => $thread->last_reply_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create thread', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create thread',
            ], 500);
        }
    }

    /**
     * Get replies in a thread
     */
    public function getReplies(Request $request, int $threadId): JsonResponse
    {
        try {
            $user = $request->user();
            $thread = ChatThread::with(['chatGroup', 'parentMessage.user'])->findOrFail($threadId);

            if (!$thread->chatGroup->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this thread'
                ], 403);
            }

            $replies = ChatMessage::where('thread_id', $threadId)
                ->with(['user', 'reactions.user'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($message) use ($user) {
                    return [
                        'id' => $message->id,
                        'user' => [
                            'id' => $message->user->id,
                            'name' => $message->user->name,
                            'avatar' => $message->user->avatar,
                        ],
                        'message' => $message->message,
                        'type' => $message->type,
                        'attachment_url' => $message->attachment_url,
                        'attachment_name' => $message->attachment_name,
                        'reactions' => $message->reactions->groupBy('emoji')->map(function($group, $emoji) {
                            return [
                                'emoji' => $emoji,
                                'count' => $group->count(),
                                'users' => $group->pluck('user.name')->toArray(),
                            ];
                        })->values(),
                        'is_own' => $message->user_id === $user->id,
                        'created_at' => $message->created_at->toISOString(),
                        'formatted_time' => $message->formatted_time,
                    ];
                });

            return response()->json([
                'success' => true,
                'thread' => [
                    'id' => $thread->id,
                    'parent_message' => [
                        'id' => $thread->parentMessage->id,
                        'user_name' => $thread->parentMessage->user->name,
                        'message' => $thread->parentMessage->message,
                        'created_at' => $thread->parentMessage->created_at,
                    ],
                    'reply_count' => $thread->reply_count,
                    'last_reply_at' => $thread->last_reply_at,
                ],
                'replies' => $replies,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get thread replies', [
                'error' => $e->getMessage(),
                'thread_id' => $threadId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get thread replies',
            ], 500);
        }
    }

    /**
     * Reply to a thread
     */
    public function reply(Request $request, int $threadId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'attachment' => 'sometimes|file|max:10240',
        ]);

        try {
            $user = $request->user();
            $thread = ChatThread::with('chatGroup')->findOrFail($threadId);

            if (!$thread->chatGroup->isMember($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this thread'
                ], 403);
            }

            $messageData = [
                'chat_group_id' => $thread->chat_group_id,
                'user_id' => $user->id,
                'thread_id' => $threadId,
                'is_thread_reply' => true,
                'message' => $validated['message'],
                'type' => 'text',
            ];

            // Handle file attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('chat-attachments', 'public');
                
                $messageData['attachment_url'] = \Storage::url($path);
                $messageData['attachment_name'] = $file->getClientOriginalName();
                $messageData['attachment_size'] = $file->getSize();
                $messageData['type'] = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
            }

            DB::beginTransaction();

            $message = ChatMessage::create($messageData);
            
            // Update thread
            $thread->increment('reply_count');
            $thread->update([
                'last_reply_id' => $message->id,
                'last_reply_at' => now(),
            ]);

            DB::commit();

            $message->load(['user', 'reactions']);

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->avatar,
                    ],
                    'message' => $message->message,
                    'type' => $message->type,
                    'attachment_url' => $message->attachment_url,
                    'attachment_name' => $message->attachment_name,
                    'is_own' => true,
                    'created_at' => $message->created_at->toISOString(),
                    'formatted_time' => $message->formatted_time,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reply to thread', [
                'error' => $e->getMessage(),
                'thread_id' => $threadId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reply to thread',
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Models\UserNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            $notifications = UserNotification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'action' => $notification->action,
                        'data' => $notification->data,
                        'is_read' => $notification->is_read,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                        'updated_at' => $notification->updated_at,
                    ];
                });

            Log::info('Notifications fetched', [
                'user_id' => $user->id,
                'count' => $notifications->count(),
                'unread_count' => $notifications->where('is_read', false)->count()
            ]);

            return response()->json([
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            
            $count = UserNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            Log::info('Unread count fetched', [
                'user_id' => $user->id,
                'count' => $count
            ]);

            return response()->json([
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch unread count', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch unread count',
                'count' => 0
            ], 500);
        }
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $notification = UserNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'message' => 'Notification not found'
                ], 404);
            }

            if (!$notification->is_read) {
                $notification->markAsRead();
                
                Log::info('Notification marked as read', [
                    'notification_id' => $id,
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'message' => 'Notification marked as read',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            $count = UserNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            Log::info('All notifications marked as read', [
                'user_id' => $user->id,
                'count' => $count
            ]);

            return response()->json([
                'message' => 'All notifications marked as read',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $notification = UserNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new notification and optionally send push
     * This method can be called from other parts of your application
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'type' => 'required|string',
                'title' => 'nullable|string',
                'message' => 'required|string',
                'action' => 'nullable|string',
                'data' => 'nullable|array',
                'send_push' => 'nullable|boolean'
            ]);

            $notification = UserNotification::create([
                'user_id' => $request->user_id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'action' => $request->action,
                'data' => $request->data,
                'is_read' => false
            ]);

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => $request->user_id,
                'type' => $request->type
            ]);

            // Send push notification if requested and queue is enabled
            if ($request->input('send_push', true) && config('push-notifications.queue.enabled')) {
                SendPushNotification::dispatch($notification->user, $notification);
                
                Log::info('Push notification job dispatched', [
                    'notification_id' => $notification->id
                ]);
            }

            return response()->json([
                'message' => 'Notification created successfully',
                'notification' => $notification
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
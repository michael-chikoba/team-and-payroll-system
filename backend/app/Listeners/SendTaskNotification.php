<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\UserNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Log;

class SendTaskNotification
{
    public function __construct()
    {
        //
    }

    public function handle(TaskAssigned $event): void
    {
        try {
            $task = $event->task;
            
            // Load relationships if not already loaded
            $task->loadMissing(['assignedTo', 'createdBy']);
            
            // Ensure we have the assigned user
            if (!$task->assignedTo) {
                Log::warning('Task notification skipped - no assigned user', [
                    'task_id' => $task->id
                ]);
                return;
            }
            
            // Get the creator's full name
            $creatorName = $task->createdBy 
                ? ($task->createdBy->first_name . ' ' . $task->createdBy->last_name)
                : 'System';
            
            // Determine action URL based on user role
            $actionUrl = '/employee/tasks';
            
            // Create notification in database
            $notification = UserNotification::create([
                'user_id' => $task->assigned_to,
                'type' => 'task_assigned',
                'title' => 'New Task Assigned',
                'message' => sprintf(
                    '%s assigned you a new task: "%s"',
                    $creatorName,
                    $task->title
                ),
                'action' => $actionUrl,
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'priority' => $task->priority,
                    'deadline' => $task->deadline,
                    'created_by' => $task->created_by,
                    'assigned_to' => $task->assigned_to,
                    'status' => $task->status
                ],
                'is_read' => false,
                'notifiable_id' => $task->id,
                'notifiable_type' => 'App\Models\Task'
            ]);
            
            // Dispatch push notification job
            // This will only send if user has enabled push notifications
            SendPushNotification::dispatch($task->assignedTo, $notification);
            
            Log::info('Task assignment notification created and push queued', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assigned_to' => $task->assigned_to,
                'created_by' => $task->created_by,
                'notification_id' => $notification->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create task notification', [
                'task_id' => $event->task->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
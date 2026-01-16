<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class SendTaskNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        try {
            $task = $event->task;
            
            // Load relationships if not already loaded
            $task->loadMissing(['assignedTo', 'createdBy']);
            
            // Get the creator's full name
            $creatorName = $task->createdBy->first_name . ' ' . $task->createdBy->last_name;
            
            // Determine action URL based on user role
            // For employees, the route name is 'TaskBoard'
            $actionUrl = '/employee/tasks'; // This will match the TaskBoard route
            
            // Create notification for the assigned employee
            UserNotification::create([
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
            
            Log::info('Task assignment notification created', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assigned_to' => $task->assigned_to,
                'created_by' => $task->created_by,
                'creator_name' => $creatorName,
                'action_url' => $actionUrl
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
<?php

namespace App\Listeners;

use App\Events\ScheduleAssigned;
use App\Events\ScheduleUpdated;
use App\Models\UserNotification;
use App\Models\ScheduleNotification;
use Illuminate\Support\Facades\Log;

class SendScheduleNotification
{
    public function __construct()
    {
        //
    }

    public function handle($event): void
    {
        try {
            $schedule = $event->schedule;
            
            // Load relationships - use assignedEmployee instead of user
            $schedule->loadMissing(['assignedEmployee', 'assignedUser', 'createdBy']);
            
            // Check for assigned employee first, then assigned user
            $assignedEmployee = $schedule->assignedEmployee;
            $assignedUser = $schedule->assignedUser;
            
            if (!$assignedEmployee && !$assignedUser) {
                Log::warning('Schedule notification skipped - no assigned employee or user', [
                    'schedule_id' => $schedule->id
                ]);
                return;
            }

            // Determine the user ID to notify
            $notifyUserId = null;
            if ($assignedEmployee && $assignedEmployee->user_id) {
                $notifyUserId = $assignedEmployee->user_id;
            } elseif ($assignedUser) {
                $notifyUserId = $assignedUser->id;
            }
            
            if (!$notifyUserId) {
                Log::warning('Schedule notification skipped - assigned employee has no user account', [
                    'schedule_id' => $schedule->id,
                    'employee_id' => $assignedEmployee->id ?? null
                ]);
                return;
            }

            // Determine notification type and message
            $isUpdate = $event instanceof ScheduleUpdated;
            $type = $isUpdate ? 'schedule_updated' : 'schedule_assigned';
            $title = $isUpdate ? 'Schedule Updated' : 'New Schedule Assigned';
            
            $creatorName = $schedule->createdBy 
                ? ($schedule->createdBy->first_name . ' ' . $schedule->createdBy->last_name)
                : 'System';
            
            $message = $isUpdate
                ? sprintf('%s updated your schedule: "%s"', $creatorName, $schedule->title)
                : sprintf('%s assigned you a new schedule: "%s"', $creatorName, $schedule->title);
            
            // Create UserNotification
            UserNotification::create([
                'user_id' => $notifyUserId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action' => '/employee/schedules',
                'data' => [
                    'schedule_id' => $schedule->id,
                    'schedule_title' => $schedule->title,
                    'schedule_date' => $schedule->scheduled_date,
                    'due_date' => $schedule->due_date,
                    'created_by' => $schedule->created_by,
                    'assigned_to' => $schedule->assigned_to,
                    'type' => $schedule->type,
                    'priority' => $schedule->priority
                ],
                'is_read' => false,
                'notifiable_id' => $schedule->id,
                'notifiable_type' => 'App\Models\Schedule'
            ]);
            
            // Create ScheduleNotification for employee tracking
            if ($assignedEmployee) {
                ScheduleNotification::create([
                    'schedule_id' => $schedule->id,
                    'employee_id' => $assignedEmployee->id,
                    'user_id' => $notifyUserId,
                    'type' => $isUpdate ? 'schedule_updated' : 'schedule_assigned',
                    'notification_type' => $isUpdate ? 'update' : 'assignment',
                    'message' => $message,
                    'is_read' => false,
                ]);
            }
            
            Log::info('Schedule notification created', [
                'schedule_id' => $schedule->id,
                'type' => $type,
                'user_id' => $notifyUserId,
                'employee_id' => $assignedEmployee->id ?? null,
                'creator' => $creatorName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create schedule notification', [
                'schedule_id' => $event->schedule->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
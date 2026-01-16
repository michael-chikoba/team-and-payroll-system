<?php

namespace App\Notifications;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ScheduleNotification extends Notification
{
    use Queueable;

    private $schedule;
    private $action; // 'assigned', 'updated', 'completed', etc.

    /**
     * Create a new notification instance.
     */
    public function __construct(Schedule $schedule, string $action)
    {
        $this->schedule = $schedule;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // You can add 'mail' here if you want email notifications
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $message = $this->getMessage();
        
        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line("Schedule: {$this->schedule->title}")
            ->line("Priority: " . ucfirst($this->schedule->priority))
            ->line("Due Date: " . $this->schedule->due_date->format('M d, Y'))
            ->action('View Schedule', url('/schedules/' . $this->schedule->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'schedule_id' => $this->schedule->id,
            'title' => $this->schedule->title,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'priority' => $this->schedule->priority,
            'due_date' => $this->schedule->due_date,
            'type' => 'schedule_' . $this->action,
            'link' => '/schedules/' . $this->schedule->id
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the notification subject based on action.
     */
    private function getSubject(): string
    {
        return match($this->action) {
            'assigned' => 'New Schedule Assigned',
            'updated' => 'Schedule Updated',
            'completed' => 'Schedule Completed',
            'overdue' => 'Schedule Overdue',
            default => 'Schedule Notification'
        };
    }

    /**
     * Get the notification message based on action.
     */
    private function getMessage(): string
    {
        return match($this->action) {
            'assigned' => "You have been assigned to: {$this->schedule->title}",
            'updated' => "Schedule has been updated: {$this->schedule->title}",
            'completed' => "Schedule has been completed: {$this->schedule->title}",
            'overdue' => "Schedule is now overdue: {$this->schedule->title}",
            default => "Schedule notification: {$this->schedule->title}"
        };
    }
}
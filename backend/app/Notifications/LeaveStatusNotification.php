<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LeaveStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Leave $leave, public string $status)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = "Leave Request {$this->status}";
        $greeting = "Hello {$notifiable->name},";

        if ($this->status === 'approved') {
            $message = "Your leave request from {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')} has been approved.";
        } else {
            $message = "Your leave request from {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')} has been rejected.";
            
            if ($this->leave->manager_notes) {
                $message .= " Reason: {$this->leave->manager_notes}";
            }
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->action('View Leave Details', url("/leaves/{$this->leave->id}"))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'type' => $this->leave->type,
            'status' => $this->status,
            'start_date' => $this->leave->start_date,
            'end_date' => $this->leave->end_date,
            'manager_notes' => $this->leave->manager_notes,
            'message' => $this->getDatabaseMessage(),
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    private function getDatabaseMessage(): string
    {
        $baseMessage = "Your {$this->leave->type} leave request from {$this->leave->start_date->format('M d')} to {$this->leave->end_date->format('M d')} has been {$this->status}";

        if ($this->status === 'rejected' && $this->leave->manager_notes) {
            $baseMessage .= ". Reason: {$this->leave->manager_notes}";
        }

        return $baseMessage . '.';
    }
}
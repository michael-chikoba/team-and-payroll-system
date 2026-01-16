<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveStatusNotification extends Notification
{
    use Queueable;
    private $leave;
    private $status;

    public function __construct($leave, $status)
    {
        $this->leave = $leave;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = "Your Leave Request Has Been " . ucfirst($this->status);
        $message = "Your leave request from {$this->leave->start_date} to {$this->leave->end_date} has been {$this->status}.";
        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('View Leave', url('/leaves/' . $this->leave->id));
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'leave_' . $this->status,
            'message' => "Your leave request has been {$this->status}.",
            'link' => '/leaves/' . $this->leave->id
        ];
    }
}
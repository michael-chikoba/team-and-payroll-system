<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketNotification extends Notification
{
    use Queueable;
    private $ticket;
    private $action; // e.g., 'created', 'updated', 'resolved'

    public function __construct($ticket, $action)
    {
        $this->ticket = $ticket;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = "Ticket #{$this->ticket->id} Has Been " . ucfirst($this->action);
        return (new MailMessage)
            ->subject($subject)
            ->line("Your ticket '{$this->ticket->subject}' has been {$this->action}.")
            ->action('View Ticket', url('/tickets/' . $this->ticket->id));
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ticket_' . $this->action,
            'message' => "Ticket #{$this->ticket->id} has been {$this->action}.",
            'link' => '/tickets/' . $this->ticket->id
        ];
    }
}
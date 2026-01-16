<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MessageNotification extends Notification
{
    use Queueable;
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Message Received')
            ->line("You have a new message from {$this->message->sender->name}: \"{$this->message->content}\"")
            ->action('View Message', url('/messages/' . $this->message->id));
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'message',
            'message' => "New message from {$this->message->sender->name}.",
            'link' => '/messages/' . $this->message->id
        ];
    }
}
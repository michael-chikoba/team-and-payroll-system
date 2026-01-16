<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Notifications\MessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendMessageNotification implements ShouldQueue
{
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;
        $user = $message->receiver;

        Notification::send($user, new MessageNotification($message));
    }
}
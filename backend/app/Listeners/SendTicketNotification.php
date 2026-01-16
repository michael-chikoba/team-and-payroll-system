<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Events\TicketResolved;
use App\Notifications\TicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTicketNotification implements ShouldQueue
{
    public function handle($event): void
    {
        $ticket = $event->ticket;
        $user = $ticket->user;
        $action = match (get_class($event)) {
            TicketCreated::class => 'created',
            TicketUpdated::class => 'updated',
            TicketResolved::class => 'resolved',
            default => 'updated',
        };

        Notification::send($user, new TicketNotification($ticket, $action));
    }
}
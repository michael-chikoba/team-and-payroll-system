<?php

namespace App\Notifications;

use App\Models\SupervisorMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * SupervisorMessageNotification
 *
 * Fires when either the employee or the supervisor sends a new
 * message in their private thread. Delivered via the database
 * channel (visible in the existing /api/notifications endpoint)
 * and broadcast for real-time badge updates.
 *
 * To also send an email: add 'mail' to via() and implement toMail().
 */
class SupervisorMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupervisorMessage $message,
        public readonly User              $sender
    ) {}

    // ── Channels ─────────────────────────────────────────────────

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // ── Database payload ──────────────────────────────────────────

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'supervisor_message',
            'message_id'    => $this->message->id,
            'sender_id'     => $this->sender->id,
            'sender_name'   => $this->sender->first_name . ' ' . $this->sender->last_name,
            'preview'       => mb_substr($this->message->message, 0, 80) . (mb_strlen($this->message->message) > 80 ? '…' : ''),
            'category'      => $this->message->category,
            'employee_id'   => $this->message->employee_id,
            'sent_at'       => $this->message->created_at->toISOString(),
        ];
    }

    // ── Broadcast payload (Echo / Pusher) ─────────────────────────

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
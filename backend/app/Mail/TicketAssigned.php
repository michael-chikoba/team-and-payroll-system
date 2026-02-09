<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $assignedUser;
    public $role;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $assignedUser, $role = 'assignee')
    {
        $this->ticket = $ticket;
        $this->assignedUser = $assignedUser;
        $this->role = $role;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $roleLabel = ucfirst($this->role);
        return new Envelope(
            subject: "Ticket #{$this->ticket->id} Assigned to You as {$roleLabel}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket.assigned',
            with: [
                'ticket' => $this->ticket,
                'assignedUser' => $this->assignedUser,
                'role' => $this->role,
                'roleLabel' => ucfirst($this->role),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
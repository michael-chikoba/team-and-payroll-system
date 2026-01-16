<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $url;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->url = config('app.frontend_url') . '/tickets/' . $ticket->id;
    }

    public function build()
    {
        return $this->subject('Ticket Approval Request: ' . $this->ticket->title)
                    ->markdown('emails.ticket-approval-request')
                    ->with([
                        'ticket' => $this->ticket,
                        'url' => $this->url,
                    ]);
    }
}
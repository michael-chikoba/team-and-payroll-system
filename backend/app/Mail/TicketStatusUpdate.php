<?php
// app/Mail/TicketStatusUpdate.php
namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build()
    {
        return $this->subject('Ticket Status Updated: ' . $this->ticket->title)
                    ->markdown('emails.ticket-status-update')
                    ->with([
                        'ticket' => $this->ticket,
                        'url' => config('app.frontend_url') . '/tickets/' . $this->ticket->id,
                    ]);
    }
}
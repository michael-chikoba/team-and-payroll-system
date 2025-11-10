<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Leave $leave)
    {
    }

    public function build(): self
    {
        return $this->subject('Leave Request Update - ' . config('app.name'))
            ->markdown('emails.leave-rejected')
            ->with([
                'leave' => $this->leave,
                'employeeName' => $this->leave->employee->user->name,
                'startDate' => $this->leave->start_date->format('M d, Y'),
                'endDate' => $this->leave->end_date->format('M d, Y'),
                'type' => $this->leave->type,
                'managerNotes' => $this->leave->manager_notes,
            ]);
    }
}
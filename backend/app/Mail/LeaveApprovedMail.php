<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Leave $leave)
    {
    }

    public function build(): self
    {
        return $this->subject('Leave Request Approved - ' . config('app.name'))
            ->markdown('emails.leave-approved')
            ->with([
                'leave' => $this->leave,
                'employeeName' => $this->leave->employee->user->name,
                'startDate' => $this->leave->start_date->format('M d, Y'),
                'endDate' => $this->leave->end_date->format('M d, Y'),
                'type' => $this->leave->type,
            ]);
    }
}
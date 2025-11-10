<?php

namespace App\Mail;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payslip $payslip)
    {
    }

    public function build(): self
    {
        return $this->subject('Your Payslip for ' . $this->payslip->payroll->payroll_period)
            ->markdown('emails.payslip-notification')
            ->attach(storage_path('app/' . $this->payslip->pdf_path), [
                'as' => 'payslip.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
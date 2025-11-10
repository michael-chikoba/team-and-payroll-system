<?php

namespace App\Jobs;

use App\Mail\PayslipMail;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPayslipEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payslip $payslip)
    {
    }

    public function handle(): void
    {
        $payslip = $this->payslip->load(['employee.user', 'payroll']);
        
        if (!$payslip->pdf_path) {
            \Log::warning("Cannot send payslip email - PDF not generated for payslip {$payslip->id}");
            return;
        }
        
        Mail::to($payslip->employee->user->email)
            ->send(new PayslipMail($payslip));
            
        $payslip->markAsSent();
        
        \App\Models\AuditLog::log(
            'payslip_sent',
            "Payslip sent to {$payslip->employee->user->email}",
            ['payslip_id' => $payslip->id, 'employee_id' => $payslip->employee_id]
        );
    }
}
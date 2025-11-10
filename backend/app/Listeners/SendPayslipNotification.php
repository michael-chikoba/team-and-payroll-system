<?php

namespace App\Listeners;

use App\Events\PayslipGenerated;
use App\Notifications\PayslipReadyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPayslipNotification implements ShouldQueue
{
    public function handle(PayslipGenerated $event): void
    {
        $payslip = $event->payslip;
        $employee = $payslip->employee;
        
        if ($employee->user) {
            Notification::send($employee->user, new PayslipReadyNotification($payslip));
        }
        
        \App\Models\AuditLog::log(
            'payslip_notification_sent',
            "Payslip notification sent to {$employee->user->email}",
            ['payslip_id' => $payslip->id, 'employee_id' => $employee->id]
        );
    }

    public function failed(PayslipGenerated $event, \Throwable $exception): void
    {
        \Log::error("Failed to send payslip notification for payslip {$event->payslip->id}: " . $exception->getMessage());
    }
}
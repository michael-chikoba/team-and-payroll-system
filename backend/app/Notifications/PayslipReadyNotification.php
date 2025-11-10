<?php

namespace App\Notifications;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PayslipReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public Payslip $payslip)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $payrollPeriod = $this->payslip->payroll->payroll_period;
        $netPay = number_format($this->payslip->net_pay, 2);

        return (new MailMessage)
            ->subject("Payslip Ready - {$payrollPeriod}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your payslip for {$payrollPeriod} is now available.")
            ->line("Net Pay: $ {$netPay}")
            ->action('Download Payslip', url("/payslips/{$this->payslip->id}/download"))
            ->line('Thank you for your hard work!');
    }

    public function toArray($notifiable): array
    {
        return [
            'payslip_id' => $this->payslip->id,
            'payroll_period' => $this->payslip->payroll->payroll_period,
            'net_pay' => $this->payslip->net_pay,
            'message' => "Your payslip for {$this->payslip->payroll->payroll_period} is ready. Net Pay: $ {$this->payslip->net_pay}",
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
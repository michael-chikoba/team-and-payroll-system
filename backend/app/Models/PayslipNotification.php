<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PayslipNotification extends Notification
{
    use Queueable;
    private $payslip;

    public function __construct($payslip)
    {
        $this->payslip = $payslip;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Payslip is Ready')
            ->view('emails.payslip', [
                'employeeName' => $notifiable->name,
                'payPeriod' => $this->payslip->pay_period,
                'grossPay' => $this->payslip->gross_pay,
                'netPay' => $this->payslip->net_pay,
                'payDate' => $this->payslip->pay_date,
                'payslipUrl' => url('/payslips/' . $this->payslip->id)
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'payslip',
            'message' => "Your payslip for {$this->payslip->pay_period} is available.",
            'link' => '/payslips/' . $this->payslip->id
        ];
    }
}
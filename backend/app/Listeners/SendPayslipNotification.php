<?php

namespace App\Listeners;

use App\Events\PayslipGenerated;
use App\Jobs\SendPayslipEmail;

class SendPayslipNotification
{
    public function handle(PayslipGenerated $event)
    {
        SendPayslipEmail::dispatch($event->payslip);
    }
}
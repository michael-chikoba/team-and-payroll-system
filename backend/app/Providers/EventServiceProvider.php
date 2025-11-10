<?php

namespace App\Providers;

use App\Events\LeaveApproved;
use App\Events\LeaveRejected;
use App\Events\PayslipGenerated;
use App\Listeners\SendLeaveApprovalNotification;
use App\Listeners\SendLeaveRejectionNotification;
use App\Listeners\SendPayslipNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LeaveApproved::class => [
            SendLeaveApprovalNotification::class,
        ],
        LeaveRejected::class => [
            SendLeaveRejectionNotification::class,
        ],
        PayslipGenerated::class => [
            SendPayslipNotification::class,
        ],
          \App\Events\LeaveStatusUpdated::class => [
        \App\Listeners\NotifyEmployeeOfLeaveStatus::class,
    ],
    ];

    public function boot(): void
    {
        //
    }
}
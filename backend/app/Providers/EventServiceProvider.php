<?php

namespace App\Providers;

use App\Events\LeaveApproved;
use App\Events\LeaveRejected;
use App\Events\PayslipGenerated;
use App\Events\ScheduleAssigned;
use App\Events\ScheduleUpdated;
use App\Events\TaskAssigned;
use App\Listeners\SendLeaveApprovalNotification;
use App\Listeners\SendTaskNotification;
use App\Listeners\SendLeaveRejectionNotification;
use App\Listeners\SendPayslipNotification;
use App\Listeners\SendScheduleNotification;
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
    \App\Events\TicketCreated::class => [
        \App\Listeners\SendTicketNotification::class,
    ],
    \App\Events\TicketUpdated::class => [
            \App\Listeners\SendTicketNotification::class,
        ],
        \App\Events\TicketResolved::class => [
            \App\Listeners\SendTicketNotification::class,
        ],
        \App\Events\ScheduleAssigned::class => [
            \App\Listeners\SendScheduleNotification::class,
        ],
        \App\Events\ScheduleUpdated::class => [
            \App\Listeners\SendScheduleNotification::class,
        ],
        \App\Events\MessageReceived::class => [
            \App\Listeners\SendMessageNotification::class,
        ],
        TaskAssigned::class => [
            SendTaskNotification::class,
        ],
         ScheduleAssigned::class => [
            SendScheduleNotification::class,
        ],
        ScheduleUpdated::class => [
            SendScheduleNotification::class,
        ],
        \App\Events\MessageReceived::class => [
            \App\Listeners\SendMessageNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
<?php

namespace App\Listeners;

use App\Events\LeaveStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyEmployeeOfLeaveStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeaveStatusUpdated $event): void
    {
        //
    }
}

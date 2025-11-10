<?php

namespace App\Listeners;

use App\Events\LeaveRejected;
use App\Mail\LeaveRejectedMail;
use App\Notifications\LeaveStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendLeaveRejectionNotification implements ShouldQueue
{
    public function handle(LeaveRejected $event): void
    {
        $leave = $event->leave;
        $employee = $leave->employee;
        
        // Send email notification
        Mail::to($employee->user->email)
            ->send(new LeaveRejectedMail($leave));
            
        // Send in-app notification
        Notification::send($employee->user, new LeaveStatusNotification($leave, 'rejected'));
        
        // Log the action
        \App\Models\AuditLog::log(
            'leave_rejected',
            "Leave request #{$leave->id} rejected for {$employee->user->name}",
            ['leave_id' => $leave->id, 'employee_id' => $employee->id],
            auth()->id()
        );
    }
}
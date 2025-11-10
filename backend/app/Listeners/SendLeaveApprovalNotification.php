<?php

namespace App\Listeners;

use App\Events\LeaveApproved;
use App\Mail\LeaveApprovedMail;
use App\Notifications\LeaveStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendLeaveApprovalNotification implements ShouldQueue
{
    public function handle(LeaveApproved $event): void
    {
        $leave = $event->leave;
        $employee = $leave->employee;
        
        // Send email notification
        Mail::to($employee->user->email)
            ->send(new LeaveApprovedMail($leave));
            
        // Send in-app notification
        Notification::send($employee->user, new LeaveStatusNotification($leave, 'approved'));
        
        // Log the action
        \App\Models\AuditLog::log(
            'leave_approved',
            "Leave request #{$leave->id} approved for {$employee->user->name}",
            ['leave_id' => $leave->id, 'employee_id' => $employee->id],
            auth()->id()
        );
    }
}
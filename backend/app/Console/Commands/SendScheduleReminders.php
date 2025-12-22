<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;

class SendScheduleReminders extends Command
{
    protected $signature = 'schedule:send-reminders';
    protected $description = 'Send reminders for upcoming and overdue schedules';

    public function handle()
    {
        $this->info('Checking for schedules requiring reminders...');

        // Get upcoming schedules (due in next 24 hours)
        $upcomingSchedules = Schedule::upcoming(24)->get();

        foreach ($upcomingSchedules as $schedule) {
            $hoursUntilDue = now()->diffInHours($schedule->due_date);
            
            $schedule->notifications()->create([
                'type' => 'reminder',
                'message' => "Reminder: '{$schedule->title}' is due in {$hoursUntilDue} hours"
            ]);
        }

        $this->info("Sent {$upcomingSchedules->count()} upcoming reminders");

        // Get overdue schedules
        $overdueSchedules = Schedule::overdue()->get();

        foreach ($overdueSchedules as $schedule) {
            // Update status
            $schedule->update(['status' => 'overdue']);

            // Check if we've already sent an overdue notification today
            $existingNotification = $schedule->notifications()
                ->where('type', 'overdue')
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (!$existingNotification) {
                $schedule->notifications()->create([
                    'type' => 'overdue',
                    'message' => "OVERDUE: '{$schedule->title}' was due on {$schedule->due_date->format('M d, Y')}"
                ]);
            }
        }

        $this->info("Processed {$overdueSchedules->count()} overdue schedules");
        $this->info('Reminder check completed!');

        return 0;
    }
}
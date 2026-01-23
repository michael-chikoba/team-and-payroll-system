<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityTrackingService;

class CheckIdleOvertimeSessions extends Command
{
    protected $signature = 'sessions:check-idle';
    protected $description = 'Check for idle overtime sessions';

    public function handle(ActivityTrackingService $service)
    {
        $this->info('Checking idle overtime sessions...');
        $result = $service->checkIdleSessions();
        
        if (isset($result['error'])) {
            $this->error('Error: ' . $result['error']);
            return Command::FAILURE;
        }
        
        $this->info('Completed successfully!');
        $this->info('Checked sessions: ' . $result['checked']);
        $this->info('Auto-closed sessions: ' . $result['auto_clocked_out']);
        $this->info('Still active sessions: ' . $result['still_active']);
        
        if (!empty($result['details'])) {
            $this->table(['Employee', 'Action', 'Time'], 
                array_map(function($detail) {
                    return [
                        $detail['employee_name'],
                        $detail['action'],
                        $detail['clock_out_time']
                    ];
                }, $result['details'])
            );
        }
        
        return Command::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMissingEmployeeProfiles extends Command
{
    protected $signature = 'employees:create-missing';
    protected $description = 'Create employee profiles for users that don\'t have one';

    public function handle()
    {
        $usersWithoutEmployee = User::doesntHave('employee')->get();
        
        if ($usersWithoutEmployee->isEmpty()) {
            $this->info('All users already have employee profiles.');
            return 0;
        }

        $this->info("Found {$usersWithoutEmployee->count()} users without employee profiles.");
        
        $bar = $this->output->createProgressBar($usersWithoutEmployee->count());
        $bar->start();

        $created = 0;
        $failed = 0;

        foreach ($usersWithoutEmployee as $user) {
            try {
                // Generate next employee ID
                $lastEmployee = Employee::orderBy('employee_id', 'desc')->first();
                
                if ($lastEmployee && preg_match('/EMP(\d+)/', $lastEmployee->employee_id, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                } else {
                    $nextNumber = 1;
                }
                
                $employeeId = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                // Determine default values based on role
                $position = match($user->role) {
                    'admin' => 'Administrator',
                    'manager' => 'Manager',
                    'employee' => 'Staff Member',
                    default => 'Staff Member'
                };

                $department = match($user->role) {
                    'admin' => 'Administration',
                    'manager' => 'Management',
                    default => 'General'
                };

                Employee::create([
                    'user_id' => $user->id,
                    'employee_id' => $employeeId,
                    'position' => $position,
                    'department' => $department,
                    'base_salary' => 0.00,
                    'hire_date' => $user->created_at ?? now(),
                    'employment_type' => 'full_time',
                ]);

                $created++;
                $bar->advance();
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nFailed to create employee profile for user {$user->id} ({$user->email}): {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Created {$created} employee profiles");
        
        if ($failed > 0) {
            $this->warn("✗ Failed to create {$failed} employee profiles");
        }

        return 0;
    }
}
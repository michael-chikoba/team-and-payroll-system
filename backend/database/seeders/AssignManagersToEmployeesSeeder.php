<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Manager;

class AssignManagersToEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereNull('manager_id')->get();
        $managers = Manager::all();

        if ($managers->isEmpty()) {
            $this->command->warn('No managers found. Please create managers first.');
            return;
        }

        $assignedCount = 0;

        foreach ($employees as $employee) {
            // Try to find a manager in the same department
            $manager = $managers->first(function ($manager) use ($employee) {
                return $manager->department === $employee->department;
            });

            // If no manager in same department, assign any manager
            if (!$manager) {
                $manager = $managers->first();
            }

            if ($manager) {
                $employee->update(['manager_id' => $manager->user_id]);
                $assignedCount++;
                
                $this->command->info("Assigned manager {$manager->user->name} to employee {$employee->full_name}");
            }
        }

        $this->command->info("Successfully assigned managers to {$assignedCount} employees.");
    }
}
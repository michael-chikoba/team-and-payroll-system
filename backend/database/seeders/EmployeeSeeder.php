<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Employee data - using existing users by email
        $employeeData = [
            [
                'email' => 'alice@payroll.com',
                'employee_id' => 'EMP0001',
                'position' => 'Software Developer',
                'department' => 'Engineering',
                'base_salary' => 75000,
                'hire_date' => Carbon::now()->subYears(2),
                'employment_type' => 'full_time',
            ],
            [
                'email' => 'bob@payroll.com', 
                'employee_id' => 'EMP0002',
                'position' => 'Marketing Specialist',
                'department' => 'Marketing',
                'base_salary' => 60000,
                'hire_date' => Carbon::now()->subYears(1),
                'employment_type' => 'full_time',
            ],
            [
                'email' => 'carol@payroll.com',
                'employee_id' => 'EMP0003',
                'position' => 'Sales Representative', 
                'department' => 'Sales',
                'base_salary' => 55000,
                'hire_date' => Carbon::now()->subMonths(6),
                'employment_type' => 'full_time',
            ],
        ];

        foreach ($employeeData as $data) {
            // Find the existing user by email
            $user = User::where('email', $data['email'])->first();
            
            if ($user) {
                // Remove email from the data since it's not in the employees table
                $employeeData = $data;
                unset($employeeData['email']);
                
                // Create or update the employee record
                Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    $employeeData
                );
                
                $this->command->info("Created employee record for: {$user->name}");
            } else {
                $this->command->error("User not found for email: {$data['email']}");
            }
        }

        $this->command->info('Employee seeding completed!');
    }
}
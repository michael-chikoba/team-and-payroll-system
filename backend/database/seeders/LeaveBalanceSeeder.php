<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\SystemSetting;

class LeaveBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $currentYear = date('Y');
        $settings = SystemSetting::getAllSettings();

        $defaultBalances = [
            'annual' => $settings['annual_leave_days'] ?? 21,
            'sick' => $settings['sick_leave_days'] ?? 7,
            'maternity' => $settings['maternity_leave_days'] ?? 90,
            'paternity' => $settings['paternity_leave_days'] ?? 14,
        ];

        foreach ($employees as $employee) {
            foreach ($defaultBalances as $type => $days) {
                // Check if balance already exists
                $existingBalance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('type', $type)
                    ->where('year', $currentYear)
                    ->first();

                if (!$existingBalance) {
                    LeaveBalance::create([
                        'employee_id' => $employee->id,
                        'type' => $type,
                        'balance' => $days,
                        'allocated_days' => $days,
                        'used_days' => 0,
                        'carried_over' => 0,
                        'year' => $currentYear,
                    ]);
                }
            }
        }
    }
}
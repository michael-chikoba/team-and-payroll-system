<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\SystemSetting;

class InitializeLeaveBalances extends Command
{
    protected $signature = 'leaves:initialize 
                            {--business= : Business ID}
                            {--employee= : Specific Employee ID}
                            {--year= : Year (default: current year)}';
    
    protected $description = 'Initialize leave balances for employees';

    public function handle()
    {
        $year = $this->option('year') ?? now()->year;
        
        $query = Employee::with(['country', 'business']);

        if ($businessId = $this->option('business')) {
            $query->where('business_id', $businessId);
            $this->info("Filtering by business ID: {$businessId}");
        }

        if ($employeeId = $this->option('employee')) {
            $query->where('id', $employeeId);
            $this->info("Filtering by employee ID: {$employeeId}");
        }

        $employees = $query->get();
        
        if ($employees->isEmpty()) {
            $this->error('No employees found with the specified criteria.');
            return 1;
        }

        $this->info("Found {$employees->count()} employee(s) to initialize");
        $this->info("Year: {$year}");
        $this->newLine();

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $result = $this->initializeForEmployee($employee, $year);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $skipCount++;
                }
                
                $bar->advance();
                
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = [
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("=== Initialization Summary ===");
        $this->info("Total Employees: {$employees->count()}");
        $this->info("✓ Successfully Initialized: {$successCount}");
        $this->info("⊘ Skipped (Already Exists): {$skipCount}");
        $this->info("✗ Failed: {$failCount}");

        if (!empty($errors)) {
            $this->newLine();
            $this->error("=== Errors ===");
            foreach ($errors as $error) {
                $this->error("Employee {$error['employee_id']} ({$error['name']}): {$error['error']}");
            }
        }

        $this->newLine();
        $this->info("Leave balances initialization completed!");
        
        return 0;
    }

    /**
     * Initialize leave balances for a single employee
     */
    private function initializeForEmployee(Employee $employee, int $year): array
    {
        $leaveTypes = ['annual', 'sick', 'maternity', 'paternity', 'bereavement'];
        $initialized = [];
        $skipped = [];

        foreach ($leaveTypes as $type) {
            // Check if balance already exists
            $existing = LeaveBalance::where('employee_id', $employee->id)
                ->where('type', $type)
                ->where('year', $year)
                ->first();

            if ($existing) {
                $skipped[] = $type;
                continue;
            }

            // Get allocation from system settings
            $allocatedDays = $this->getLeaveSettingForEmployee($employee, $type);
            
            // Skip if no days allocated
            if ($allocatedDays === 0) {
                continue;
            }

            // Create new balance
            LeaveBalance::create([
                'employee_id' => $employee->id,
                'type' => $type,
                'year' => $year,
                'allocated_days' => $allocatedDays,
                'used_days' => 0,
                'carried_over' => 0,
                'balance' => $allocatedDays,
            ]);

            $initialized[] = $type;
        }

        return [
            'success' => !empty($initialized) || !empty($skipped),
            'initialized' => $initialized,
            'skipped' => $skipped
        ];
    }

    /**
     * Get leave setting for employee from system_settings
     */
    private function getLeaveSettingForEmployee(Employee $employee, string $leaveType): int
    {
        $settingKeys = [
            'annual' => 'annual_leave_days',
            'sick' => 'sick_leave_days',
            'maternity' => 'maternity_leave_days',
            'paternity' => 'paternity_leave_days',
            'bereavement' => 'bereavement_leave_days',
        ];

        $settingKey = $settingKeys[$leaveType] ?? null;
        
        if (!$settingKey) {
            return 0;
        }

        $countryCode = $employee->country->code ?? 'ZM';

        // Priority: Business+Country > Business > Global
        $setting = SystemSetting::where('key', $settingKey)
            ->where(function($q) use ($employee, $countryCode) {
                // Specific business + country
                $q->where(function($sq) use ($employee, $countryCode) {
                    $sq->where('business_id', $employee->business_id)
                       ->where('country_code', $countryCode);
                })
                // Or specific business (global)
                ->orWhere(function($sq) use ($employee) {
                    $sq->where('business_id', $employee->business_id)
                       ->where('country_code', 'global');
                })
                // Or global setting
                ->orWhere(function($sq) {
                    $sq->whereNull('business_id')
                       ->where('country_code', 'global');
                });
            })
            ->orderByRaw("
                CASE 
                    WHEN business_id = ? AND country_code = ? THEN 1
                    WHEN business_id = ? AND country_code = 'global' THEN 2
                    WHEN business_id IS NULL AND country_code = 'global' THEN 3
                    ELSE 4
                END
            ", [$employee->business_id, $countryCode, $employee->business_id])
            ->first();

        return (int) ($setting->value ?? 0);
    }
}
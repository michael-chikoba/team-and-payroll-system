<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\Leave;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LeaveBalanceService
{
    /**
     * Get leave allocation from system settings database
     */
    private function getLeaveAllocationFromSettings(string $type): float
    {
        // Map leave types to system setting keys
        $settingKeyMap = [
            'annual' => 'annual_leave_days',
            'sick' => 'sick_leave_days',
            'maternity' => 'maternity_leave_days',
            'paternity' => 'paternity_leave_days',
        ];

        // Check if this type has a system setting
        if (!isset($settingKeyMap[$type])) {
            // Fall back to config for types not in system settings
            return (float) config("payroll.leave.default_balances.{$type}", 0);
        }

        $settingKey = $settingKeyMap[$type];

        // Cache the setting for 5 minutes to reduce DB queries
        return Cache::remember("leave_allocation_{$type}", 300, function () use ($settingKey, $type) {
            $setting = SystemSetting::where('key', $settingKey)->first();
            
            if ($setting) {
                return (float) $setting->value;
            }

            // Fall back to config if setting doesn't exist
            return (float) config("payroll.leave.default_balances.{$type}", 0);
        });
    }

    /**
     * Get leave balance for a specific type
     */
    public function getBalance(int $employeeId, string $type, int $year = null): float
    {
        $type = strtolower($type);
        $year = $year ?? date('Y');
       
        // Validate type
        if (!in_array($type, LeaveBalance::VALID_TYPES)) {
            Log::warning("Invalid leave type requested: {$type}");
            return 0.0;
        }

        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        // If no balance exists, initialize it
        if (!$balance) {
            $balance = $this->initializeBalanceForType($employeeId, $type, $year);
        }
           
        return (float) $balance->balance;
    }

    /**
     * Get all leave balances for an employee
     */
    public function getAllBalances(int $employeeId, int $year = null): array
    {
        $year = $year ?? date('Y');
       
        // Get existing balances
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->keyBy('type');
           
        $result = [];
       
        // Iterate through all valid types and get current allocations
        foreach (LeaveBalance::VALID_TYPES as $type) {
            // Get current allocation from system settings
            $currentAllocation = $this->getLeaveAllocationFromSettings($type);
            
            if (isset($balances[$type])) {
                $result[$type] = [
                    'type' => $type,
                    'balance' => (float) $balances[$type]->balance,
                    'allocated_days' => (float) $balances[$type]->allocated_days,
                    'used_days' => (float) $balances[$type]->used_days,
                    'carried_over' => (float) $balances[$type]->carried_over,
                ];
            } else {
                // Initialize if doesn't exist
                $newBalance = $this->initializeBalanceForType($employeeId, $type, $year, $currentAllocation);
                $result[$type] = [
                    'type' => $type,
                    'balance' => (float) $newBalance->balance,
                    'allocated_days' => (float) $newBalance->allocated_days,
                    'used_days' => (float) $newBalance->used_days,
                    'carried_over' => (float) $newBalance->carried_over,
                ];
            }
        }
       
        return $result;
    }

    /**
     * Deduct days from leave balance
     */
    public function deductBalance(int $employeeId, string $type, float $days, int $year = null): bool
    {
        $type = strtolower($type);
        $year = $year ?? date('Y');
       
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $balance = $this->initializeBalanceForType($employeeId, $type, $year);
        }
       
        return $balance->deduct($days);
    }

    /**
     * Add days to leave balance
     */
    public function addBalance(int $employeeId, string $type, float $days, int $year = null): bool
    {
        $type = strtolower($type);
        $year = $year ?? date('Y');
       
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $balance = $this->initializeBalanceForType($employeeId, $type, $year);
        }
       
        return $balance->add($days);
    }

    /**
     * Restore days to leave balance (e.g., when leave is rejected)
     */
    public function restoreBalance(int $employeeId, string $type, float $days, int $year = null): bool
    {
        $type = strtolower($type);
        $year = $year ?? date('Y');
       
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return false;
        }
       
        return $balance->restore($days);
    }

    /**
     * Check if employee has sufficient balance
     */
    public function hasSufficientBalance(int $employeeId, string $type, float $days, int $year = null): bool
    {
        $balance = $this->getBalance($employeeId, $type, $year);
        return $balance >= $days;
    }

    /**
     * Initialize all leave balances for an employee
     */
    public function initializeBalances(int $employeeId, int $year = null): void
    {
        $year = $year ?? date('Y');
       
        foreach (LeaveBalance::VALID_TYPES as $type) {
            $allocatedDays = $this->getLeaveAllocationFromSettings($type);
            $this->initializeBalanceForType($employeeId, $type, $year, $allocatedDays);
        }
        
        Log::info("Initialized leave balances for employee", [
            'employee_id' => $employeeId,
            'year' => $year
        ]);
    }

    /**
     * Initialize balance for a specific leave type
     */
    private function initializeBalanceForType(
        int $employeeId, 
        string $type, 
        int $year, 
        float $allocatedDays = null
    ): LeaveBalance {
        // Get allocation from system settings if not provided
        if ($allocatedDays === null) {
            $allocatedDays = $this->getLeaveAllocationFromSettings($type);
        }

        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'type' => $type,
                'year' => $year,
            ],
            [
                'allocated_days' => $allocatedDays,
                'used_days' => 0,
                'carried_over' => 0,
                'balance' => $allocatedDays,
            ]
        );
    }

    /**
     * Update leave balances for all employees based on new system settings
     * This should be called when admin updates leave day settings
     */
    public function syncAllEmployeeBalancesWithSettings(int $year = null): int
    {
        $year = $year ?? date('Y');
        $updatedCount = 0;

        // Clear cache for leave allocations
        foreach (LeaveBalance::VALID_TYPES as $type) {
            Cache::forget("leave_allocation_{$type}");
        }

        // Get all employees
        $employees = \App\Models\Employee::all();

        foreach ($employees as $employee) {
            try {
                // Get existing balances for this employee
                $existingBalances = LeaveBalance::where('employee_id', $employee->id)
                    ->where('year', $year)
                    ->get()
                    ->keyBy('type');

                foreach (LeaveBalance::VALID_TYPES as $type) {
                    // Get new allocation from system settings
                    $newAllocation = $this->getLeaveAllocationFromSettings($type);

                    if (isset($existingBalances[$type])) {
                        $balance = $existingBalances[$type];
                        
                        // Only update if allocation has changed
                        if ($balance->allocated_days != $newAllocation) {
                            $oldAllocated = $balance->allocated_days;
                            $difference = $newAllocation - $oldAllocated;
                            
                            // Update allocated days
                            $balance->allocated_days = $newAllocation;
                            
                            // Adjust balance (add/subtract the difference)
                            $balance->balance = $balance->balance + $difference;
                            
                            // Ensure balance doesn't go negative
                            if ($balance->balance < 0) {
                                $balance->balance = 0;
                            }
                            
                            $balance->save();
                            
                            Log::info("Updated leave balance for employee", [
                                'employee_id' => $employee->id,
                                'type' => $type,
                                'old_allocated' => $oldAllocated,
                                'new_allocated' => $newAllocation,
                                'new_balance' => $balance->balance
                            ]);
                            
                            $updatedCount++;
                        }
                    } else {
                        // Create new balance if it doesn't exist
                        $this->initializeBalanceForType($employee->id, $type, $year, $newAllocation);
                        $updatedCount++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error syncing balances for employee {$employee->id}: " . $e->getMessage());
            }
        }

        Log::info("Completed syncing leave balances", [
            'total_updated' => $updatedCount,
            'year' => $year
        ]);

        return $updatedCount;
    }

    /**
     * Carry over unused balances to next year
     */
    public function carryOverBalances(int $employeeId, int $fromYear, int $toYear, array $carryOverRules = []): void
    {
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $fromYear)
            ->get();

        foreach ($balances as $balance) {
            $carryOverAmount = $this->calculateCarryOver($balance, $carryOverRules);
            
            if ($carryOverAmount > 0) {
                // Get new allocation for the new year
                $newAllocation = $this->getLeaveAllocationFromSettings($balance->type);
                
                LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'type' => $balance->type,
                        'year' => $toYear,
                    ],
                    [
                        'allocated_days' => $newAllocation,
                        'carried_over' => $carryOverAmount,
                        'used_days' => 0,
                        'balance' => $newAllocation + $carryOverAmount,
                    ]
                );
            }
        }
    }

    /**
     * Calculate carry over amount based on rules
     */
    private function calculateCarryOver(LeaveBalance $balance, array $rules = []): float
    {
        $remainingBalance = $balance->balance;
        
        // Default: no carry over for sick leave and unpaid leave
        if (in_array($balance->type, ['sick', 'unpaid'])) {
            return 0;
        }

        // Check if there are specific rules for this leave type
        if (isset($rules[$balance->type])) {
            $rule = $rules[$balance->type];
            
            if (isset($rule['max_carry_over'])) {
                return min($remainingBalance, $rule['max_carry_over']);
            }
            
            if (isset($rule['percentage'])) {
                return $remainingBalance * ($rule['percentage'] / 100);
            }
        }

        // Default: carry over all remaining annual leave (up to 50% of allocated)
        if ($balance->type === 'annual') {
            return min($remainingBalance, $balance->allocated_days * 0.5);
        }

        return 0;
    }
}
<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use App\Models\Leave;
use App\Models\SystemSetting;

class LeaveBalanceService
{
    /**
     * Helper to resolve settings (Moved inside this class)
     */
    private function getSetting(string $key, ?int $businessId, ?string $countryCode = null)
    {
        // Cache key includes business and country to ensure uniqueness
        $cacheKey = "settings_" . ($businessId ?? 'global') . "_" . ($countryCode ?? 'global') . "_{$key}";
        
        return Cache::remember($cacheKey, 600, function () use ($key, $businessId, $countryCode) {
            $settings = SystemSetting::where('key', $key)
                ->where(function($q) use ($businessId) {
                    $q->where('business_id', $businessId)
                      ->orWhereNull('business_id');
                })->get();

            // 1. Specific Business + Country
            if ($businessId && $countryCode) {
                $match = $settings->where('business_id', $businessId)
                                  ->where('country_code', $countryCode)
                                  ->first();
                if ($match) return $match->value;
            }

            // 2. Specific Business (Global Country)
            if ($businessId) {
                $match = $settings->where('business_id', $businessId)
                                  ->filter(fn($i) => is_null($i->country_code) || $i->country_code === 'global')
                                  ->first();
                if ($match) return $match->value;
            }

            // 3. Global System Default
            $match = $settings->whereNull('business_id')->first();
            
            // Return 0 if setting not found, rather than null, to prevent math errors
            return $match ? $match->value : 0;
        });
    }

    /**
     * Clear cache for a specific business
     * FIXED: Removed cache tags, using pattern-based clearing instead
     */
    private function clearBusinessCache(int $businessId): void
    {
        try {
            // Get business info to find country code
            $business = Business::with('country')->find($businessId);
            $countryCode = $business->country->code ?? null;
            
            // Clear specific cache keys for this business
            $leaveTypes = ['annual_leave_days', 'sick_leave_days', 'maternity_leave_days', 'paternity_leave_days'];
            
            foreach ($leaveTypes as $type) {
                // Clear business-specific setting caches
                Cache::forget("settings_{$businessId}_{$countryCode}_{$type}");
                Cache::forget("settings_{$businessId}_global_{$type}");
            }
            
            // Clear the main settings cache for this business
            Cache::forget("system_settings_{$businessId}_{$countryCode}");
            
            Log::info('Cache cleared for business', [
                'business_id' => $businessId,
                'country_code' => $countryCode
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to clear business cache', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available balance dynamically
     */
    public function getBalance(int $employeeId, string $type): float
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            Log::warning('Employee not found', ['employee_id' => $employeeId]);
            return 0;
        }

        // Get total allowed from system settings
        $totalAllowed = LeaveBalance::getLeaveSettingForEmployee($employee, $type);

        // Calculate used days from approved/pending leaves
        $usedDays = Leave::where('employee_id', $employeeId)
            ->where('type', $type)
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        return max(0, $totalAllowed - $usedDays);
    }

    public function hasSufficientBalance(int $employeeId, string $type, float $requestedDays): bool
    {
        $balance = $this->getBalance($employeeId, $type);
        
        Log::info('Checking leave balance', [
            'employee_id' => $employeeId,
            'type' => $type,
            'available' => $balance,
            'requested' => $requestedDays,
            'sufficient' => $balance >= $requestedDays
        ]);
        
        return $balance >= $requestedDays;
    }

    public function getAllBalances(int $employeeId): array
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [];
        }

        $balances = [];

        foreach (LeaveBalance::VALID_TYPES as $type) {
            // Get total from settings
            $totalAllowed = LeaveBalance::getLeaveSettingForEmployee($employee, $type);

            // Skip if this leave type is not configured (0 days)
            if ($totalAllowed === 0) {
                continue;
            }

            // Get used days from database
            $usedDays = Leave::where('employee_id', $employeeId)
                ->where('type', $type)
                ->whereIn('status', ['approved', 'pending'])
                ->whereYear('start_date', now()->year)
                ->sum('total_days');

            $balances[$type] = [
                'total' => $totalAllowed,
                'used' => $usedDays,
                'available' => max(0, $totalAllowed - $usedDays)
            ];
        }

        return $balances;
    }

    /**
     * Sync leave balances for all employees in a business
     * FIXED: Removed cache tags usage
     */
    public function syncEmployeeBalancesForBusiness(int $businessId, array $newLeaveSettings): int
    {
        Log::info('Syncing leave balances for business', [
            'business_id' => $businessId,
            'new_settings' => $newLeaveSettings
        ]);

        $currentYear = now()->year;
        $updatedCount = 0;

        // Clear cache for this business (without tags)
        $this->clearBusinessCache($businessId);

        $employees = Employee::where('business_id', $businessId)->get();

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                foreach (LeaveBalance::VALID_TYPES as $type) {
                    $settingKey = LeaveBalance::SETTING_KEYS[$type];
                    
                    if (isset($newLeaveSettings[$settingKey])) {
                        $newAllocation = (int) $newLeaveSettings[$settingKey];
                        
                        $balance = LeaveBalance::firstOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'type' => $type,
                                'year' => $currentYear,
                            ],
                            [
                                'allocated_days' => $newAllocation,
                                'used_days' => 0,
                                'carried_over' => 0,
                                'balance' => $newAllocation,
                            ]
                        );

                        // Update if already exists
                        if (!$balance->wasRecentlyCreated) {
                            $balance->allocated_days = $newAllocation;
                            $balance->recalculateBalance();
                        }

                        $updatedCount++;
                    }
                }
            }

            DB::commit();
            
            Log::info('Leave balances synced successfully', [
                'business_id' => $businessId,
                'updated_count' => $updatedCount
            ]);
            
            return $updatedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing leave balances', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Deduct leave days when leave is approved
     * Renamed from deductLeave to deductBalance to match Controller usage
     */
    public function deductBalance(int $employeeId, string $leaveType, float $days, ?int $year = null): bool
    {
        $year = $year ?? now()->year;

        Log::info('Deducting leave balance', [
            'employee_id' => $employeeId,
            'type' => $leaveType,
            'days' => $days,
            'year' => $year
        ]);

        try {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                throw new \Exception("Employee not found");
            }

            $balance = LeaveBalance::where('employee_id', $employeeId)
                ->where('type', $leaveType)
                ->where('year', $year)
                ->first();

            // Auto-initialize if missing
            if (!$balance) {
                Log::info('Balance not found, initializing', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType
                ]);
                
                $balances = LeaveBalance::initializeForEmployee($employee, $year);
                $balance = $balances[$leaveType] ?? null;
                
                if (!$balance) {
                    throw new \Exception("Failed to initialize leave balance");
                }
            }

            // Deduct the balance
            if (!$balance->deduct($days)) {
                Log::warning('Failed to deduct leave balance', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType,
                    'available' => $balance->balance,
                    'requested' => $days
                ]);
                return false;
            }

            Log::info('Leave balance deducted successfully', [
                'balance_id' => $balance->id,
                'new_balance' => $balance->balance
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting leave balance', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update or create a leave balance for an employee
     * FIXED: Added quotes around leave_type value to prevent SQL error
     */
    private function updateLeaveBalance(int $employeeId, string $leaveType, int $year, int $totalDays): void 
    {
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('type', $leaveType)
            ->where('year', $year)
            ->first();

        if ($balance) {
            // Recalculate available based on new total and existing usage
            $balance->update([
                'total_days' => $totalDays,
                'available_days' => max(0, $totalDays - $balance->used_days),
                'updated_at' => now()
            ]);
        } else {
            LeaveBalance::create([
                'employee_id' => $employeeId,
                'type' => $leaveType,
                'year' => $year,
                'total_days' => $totalDays,
                'used_days' => 0,
                'available_days' => $totalDays,
                'carried_forward' => 0,
            ]);
        }
    }

    public function initializeEmployeeBalances(Employee $employee): void
    {
        Log::info('Initializing leave balances for employee', [
            'employee_id' => $employee->id,
            'business_id' => $employee->business_id
        ]);

        try {
            $balances = LeaveBalance::initializeForEmployee($employee);
            
            Log::info('Leave balances initialized', [
                'employee_id' => $employee->id,
                'types_initialized' => array_keys($balances)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize leave balances', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Deduct leave days when leave is approved
     */
    public function deductLeave(int $employeeId, string $leaveType, int $year, float $days): bool
    {
        Log::info('LEAVE_BALANCE_SERVICE: Deducting leave', [
            'employee_id' => $employeeId,
            'type' => $leaveType,
            'year' => $year,
            'days' => $days
        ]);

        try {
            $balance = LeaveBalance::where('employee_id', $employeeId)
                ->where('type', $leaveType)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                Log::warning('LEAVE_BALANCE_SERVICE: No balance found', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType,
                    'year' => $year
                ]);
                return false;
            }

            if ($balance->available_days < $days) {
                Log::warning('LEAVE_BALANCE_SERVICE: Insufficient balance', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType,
                    'available' => $balance->available_days,
                    'requested' => $days
                ]);
                return false;
            }

            $balance->used_days += $days;
            $balance->available_days -= $days;
            $balance->save();

            Log::info('LEAVE_BALANCE_SERVICE: Leave deducted successfully', [
                'balance_id' => $balance->id,
                'new_used' => $balance->used_days,
                'new_available' => $balance->available_days
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('LEAVE_BALANCE_SERVICE: Error deducting leave', [
                'employee_id' => $employeeId,
                'type' => $leaveType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function restoreBalance(int $employeeId, string $leaveType, float $days, ?int $year = null): bool
    {
        $year = $year ?? now()->year;

        Log::info('Restoring leave balance', [
            'employee_id' => $employeeId,
            'type' => $leaveType,
            'days' => $days,
            'year' => $year
        ]);

        try {
            $balance = LeaveBalance::where('employee_id', $employeeId)
                ->where('type', $leaveType)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                Log::warning('Balance not found for restoration', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType
                ]);
                return false;
            }

            return $balance->restore($days);
        } catch (\Exception $e) {
            Log::error('Error restoring leave balance', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restore leave days when leave is cancelled/rejected
     */
    public function restoreLeave(int $employeeId, string $leaveType, int $year, float $days): bool
    {
        Log::info('LEAVE_BALANCE_SERVICE: Restoring leave', [
            'employee_id' => $employeeId,
            'type' => $leaveType,
            'year' => $year,
            'days' => $days
        ]);

        try {
            $balance = LeaveBalance::where('employee_id', $employeeId)
                ->where('type', $leaveType)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                Log::warning('LEAVE_BALANCE_SERVICE: No balance found to restore', [
                    'employee_id' => $employeeId,
                    'type' => $leaveType,
                    'year' => $year
                ]);
                return false;
            }

            $balance->used_days = max(0, $balance->used_days - $days);
            $balance->available_days = min(
                $balance->total_days, 
                $balance->available_days + $days
            );
            $balance->save();

            Log::info('LEAVE_BALANCE_SERVICE: Leave restored successfully', [
                'balance_id' => $balance->id,
                'new_used' => $balance->used_days,
                'new_available' => $balance->available_days
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('LEAVE_BALANCE_SERVICE: Error restoring leave', [
                'employee_id' => $employeeId,
                'type' => $leaveType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getEmployeeBalances(int $employeeId, ?int $year = null): array
    {
        $year = $year ?? now()->year;

        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->keyBy('type')
            ->map(function ($balance) {
                return [
                    'total_days' => $balance->allocated_days,
                    'used_days' => $balance->used_days,
                    'available_days' => $balance->balance,
                    'carried_forward' => $balance->carried_over,
                ];
            })
            ->toArray();

        return $balances;
    }

    /**
     * Check if employee can be assigned a shift (not on approved leave)
     */
    public function canAssignShift(int $employeeId, string $date): bool
    {
        $isOnLeave = LeaveBalance::isEmployeeOnLeave($employeeId, $date);
        
        Log::info('Checking if employee can be assigned shift', [
            'employee_id' => $employeeId,
            'date' => $date,
            'is_on_leave' => $isOnLeave,
            'can_assign' => !$isOnLeave
        ]);
        
        return !$isOnLeave;
    }

    /**
     * Get reason why employee cannot be assigned shift
     */
    public function getShiftBlockReason(int $employeeId, string $date): ?string
    {
        if (LeaveBalance::isEmployeeOnLeave($employeeId, $date)) {
            $leaveType = LeaveBalance::getLeaveType($employeeId, $date);
            return "Employee is on " . ucfirst($leaveType ?? 'approved') . " leave on this date";
        }

        return null;
    }
}
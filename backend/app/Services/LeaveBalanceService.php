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
     * Get available balance dynamically
     */
     public function getBalance(int $employeeId, string $type)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return 0;

        $settingKey = strtolower($type) . '_leave_days';
        
        // Fetch total allowed from Settings (Business/Country aware)
        $totalAllowed = (int) $this->getSetting(
            $settingKey,
            $employee->business_id, 
            $employee->country_code
        );

        $usedDays = Leave::where('employee_id', $employeeId)
            ->where('type', $type)
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        return max(0, $totalAllowed - $usedDays);
    }

    public function hasSufficientBalance(int $employeeId, string $type, int $requestedDays): bool
    {
        $balance = $this->getBalance($employeeId, $type);
        return $balance >= $requestedDays;
    }

     public function getAllBalances(int $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return [];

        $types = ['annual', 'sick', 'maternity', 'paternity', 'bereavement'];
        $balances = [];

        foreach ($types as $type) {
            $settingKey = strtolower($type) . '_leave_days';
            
            // 1. Get Total from Settings
            $totalAllowed = (int) $this->getSetting(
                $settingKey, 
                $employee->business_id, 
                $employee->country_code
            );

            // Skip if this leave type is not configured (0 days)
            if ($totalAllowed === 0) {
                continue;
            }

            // 2. Get Used Days from database
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
     */
    public function syncEmployeeBalancesForBusiness(int $businessId, array $newLeaveSettings): int
    {
        Log::info('LEAVE_BALANCE_SERVICE: Syncing balances for business', [
            'business_id' => $businessId,
            'new_settings' => $newLeaveSettings
        ]);

        $currentYear = now()->year;
        $updatedCount = 0;

        $employees = Employee::whereHas('user', function($q) use ($businessId) {
            $q->where('current_business_id', $businessId);
        })->get();

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                // Map settings keys to leave types
                $typesToSync = [
                    'annual' => 'annual_leave_days',
                    'sick' => 'sick_leave_days',
                    'maternity' => 'maternity_leave_days',
                    'paternity' => 'paternity_leave_days'
                ];

                foreach ($typesToSync as $type => $settingKey) {
                    if (isset($newLeaveSettings[$settingKey])) {
                        $this->updateLeaveBalance($employee->id, $type, $currentYear, (int)$newLeaveSettings[$settingKey]);
                        $updatedCount++;
                    }
                }
            }

            DB::commit();
            return $updatedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LEAVE_BALANCE_SERVICE: Error syncing balances', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
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

        Log::info('LEAVE_BALANCE_SERVICE: Deducting leave balance', [
            'employee_id' => $employeeId,
            'type' => $leaveType,
            'days' => $days
        ]);

        try {
            $balance = LeaveBalance::where('employee_id', $employeeId)
                ->where('type', $leaveType)
                ->where('year', $year)
                ->first();

            // Auto-initialize if missing (First leave of the year)
            if (!$balance) {
                $employee = Employee::find($employeeId);
                $settingKey = strtolower($leaveType) . '_leave_days';
                $totalAllowed = (int) $this->getSetting($settingKey, $employee->business_id, $employee->country_code);
                
                $balance = LeaveBalance::create([
                    'employee_id' => $employeeId,
                    'type' => $leaveType,
                    'year' => $year,
                    'total_days' => $totalAllowed,
                    'used_days' => 0,
                    'available_days' => $totalAllowed,
                    'carried_forward' => 0,
                ]);
            }

            // Warning for negative balance, but proceed
            if ($balance->available_days < $days) {
                 Log::warning('LEAVE_BALANCE_SERVICE: Negative balance occurred', [
                    'employee' => $employeeId,
                    'available' => $balance->available_days,
                    'requested' => $days
                ]);
            }

            $balance->used_days += $days;
            $balance->available_days = $balance->total_days - $balance->used_days; // Recalculate based on total
            $balance->save();

            return true;
        } catch (\Exception $e) {
            Log::error('LEAVE_BALANCE_SERVICE: Error deducting leave', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    /**
     * Update or create a leave balance for an employee
     * 
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

   public function initializeEmployeeBalances(Employee $employee, array $leaveSettings = []): void
    {
        $currentYear = now()->year;
        $leaveTypes = ['annual', 'sick', 'maternity', 'paternity', 'bereavement'];

        DB::beginTransaction();
        try {
            foreach ($leaveTypes as $type) {
                // 1. Try passed settings first, 2. Fetch from DB Settings
                $settingKey = strtolower($type) . '_leave_days';
                
                $days = isset($leaveSettings[$settingKey]) 
                    ? (int)$leaveSettings[$settingKey] 
                    : (int)$this->getSetting($settingKey, $employee->business_id, $employee->country_code);

                // If 0, it means this leave type isn't configured for this business
                if ($days > 0) {
                    LeaveBalance::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'type' => $type,
                            'year' => $currentYear,
                        ],
                        [
                            'total_days' => $days,
                            'used_days' => 0,
                            'available_days' => $days,
                            'carried_forward' => 0,
                        ]
                    );
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LEAVE_BALANCE_SERVICE: Init failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get employee's leave balances for a specific year
     */
    public function getEmployeeBalances(int $employeeId, int $year = null): array
    {
        $year = $year ?? now()->year;

        Log::info('LEAVE_BALANCE_SERVICE: Getting employee balances', [
            'employee_id' => $employeeId,
            'year' => $year
        ]);

        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->keyBy('type')
            ->map(function ($balance) {
                return [
                    'total_days' => $balance->total_days,
                    'used_days' => $balance->used_days,
                    'available_days' => $balance->available_days,
                    'carried_forward' => $balance->carried_forward,
                ];
            })
            ->toArray();

        Log::info('LEAVE_BALANCE_SERVICE: Balances retrieved', [
            'employee_id' => $employeeId,
            'year' => $year,
            'types' => array_keys($balances)
        ]);

        return $balances;
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
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'balance',
        'allocated_days',
        'used_days',
        'carried_over',
        'year',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'carried_over' => 'decimal:2',
        'year' => 'integer',
    ];

    // Valid leave types - must match system_settings
    public const VALID_TYPES = [
        'annual',
        'sick',
        'maternity',
        'paternity',
        'bereavement',
    ];

    // Map leave types to system_settings keys
    public const SETTING_KEYS = [
        'annual' => 'annual_leave_days',
        'sick' => 'sick_leave_days',
        'maternity' => 'maternity_leave_days',
        'paternity' => 'paternity_leave_days',
        'bereavement' => 'bereavement_leave_days',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get leave setting from system_settings table
     * Priority: Business+Country > Business > Country > Global
     */
    public static function getLeaveSettingForEmployee(Employee $employee, string $leaveType): int
    {
        $settingKey = self::SETTING_KEYS[$leaveType] ?? null;
        
        if (!$settingKey) {
            Log::warning('Invalid leave type requested', ['type' => $leaveType]);
            return 0;
        }

        $cacheKey = "leave_setting_{$employee->business_id}_{$employee->country_id}_{$settingKey}";
        
        return Cache::remember($cacheKey, 600, function () use ($employee, $settingKey) {
            $setting = \App\Models\SystemSetting::where('key', $settingKey)
                ->where(function($q) use ($employee) {
                    // Specific business + country
                    $q->where(function($sq) use ($employee) {
                        $sq->where('business_id', $employee->business_id)
                           ->where('country_code', $employee->country->code ?? 'ZM');
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
                ", [
                    $employee->business_id, 
                    $employee->country->code ?? 'ZM',
                    $employee->business_id
                ])
                ->first();

            return (int) ($setting->value ?? 0);
        });
    }

    /**
     * Initialize all leave balances for an employee based on system settings
     */
    public static function initializeForEmployee(Employee $employee, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $initialized = [];

        Log::info('Initializing leave balances', [
            'employee_id' => $employee->id,
            'business_id' => $employee->business_id,
            'country_id' => $employee->country_id,
            'year' => $year
        ]);

        foreach (self::VALID_TYPES as $type) {
            $allocatedDays = self::getLeaveSettingForEmployee($employee, $type);
            
            // Skip if no days allocated for this leave type
            if ($allocatedDays === 0) {
                Log::info("Skipping {$type} - no days allocated", [
                    'employee_id' => $employee->id
                ]);
                continue;
            }

            $balance = self::firstOrCreate(
                [
                    'employee_id' => $employee->id,
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

            $initialized[$type] = $balance;

            Log::info("Initialized {$type} leave", [
                'employee_id' => $employee->id,
                'allocated_days' => $allocatedDays,
                'balance_id' => $balance->id
            ]);
        }

        return $initialized;
    }

    /**
     * Sync balance with current system settings
     */
    public function syncWithSettings(): bool
    {
        try {
            $employee = $this->employee;
            $newAllocation = self::getLeaveSettingForEmployee($employee, $this->type);
            
            // Update allocation and recalculate balance
            $this->allocated_days = $newAllocation;
            $this->recalculateBalance();
            
            Log::info('Synced leave balance with settings', [
                'balance_id' => $this->id,
                'employee_id' => $this->employee_id,
                'type' => $this->type,
                'new_allocation' => $newAllocation
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync leave balance', [
                'balance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deduct days from balance
     */
    public function deduct(float $days): bool
    {
        if ($this->balance < $days) {
            Log::warning('Insufficient leave balance', [
                'balance_id' => $this->id,
                'available' => $this->balance,
                'requested' => $days
            ]);
            return false;
        }

        $this->used_days += $days;
        $this->balance -= $days;
        
        return $this->save();
    }

    /**
     * Add days to balance
     */
    public function add(float $days): bool
    {
        $this->balance += $days;
        $this->allocated_days += $days;
        
        return $this->save();
    }

    /**
     * Restore days to balance (e.g., when leave is rejected)
     */
    public function restore(float $days): bool
    {
        $this->used_days = max(0, $this->used_days - $days);
        $this->recalculateBalance();
        
        Log::info('Restored leave days', [
            'balance_id' => $this->id,
            'days_restored' => $days,
            'new_balance' => $this->balance
        ]);
        
        return true;
    }

    /**
     * Check if sufficient balance exists
     */
    public function hasSufficientBalance(float $days): bool
    {
        return $this->balance >= $days;
    }

    /**
     * Recalculate balance based on allocated, used, and carried over
     */
    public function recalculateBalance(): void
    {
        $this->balance = $this->allocated_days + $this->carried_over - $this->used_days;
        $this->save();
    }

    /**
     * Get summary of all leave types for employee
     */
    public static function getSummaryForEmployee(Employee $employee, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        
        $balances = self::where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('type');

        $summary = [];
        
        foreach (self::VALID_TYPES as $type) {
            $balance = $balances->get($type);
            
            $summary[$type] = [
                'allocated' => $balance ? $balance->allocated_days : 0,
                'used' => $balance ? $balance->used_days : 0,
                'balance' => $balance ? $balance->balance : 0,
                'carried_over' => $balance ? $balance->carried_over : 0,
                'exists' => $balance !== null,
            ];
        }

        return $summary;
    }

    /**
     * Check if employee is on approved leave for a specific date
     */
    public static function isEmployeeOnLeave(int $employeeId, string $date): bool
    {
        return \App\Models\Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Get leave type from a leave record
     */
    public static function getLeaveType(int $employeeId, string $date): ?string
    {
        $leave = \App\Models\Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return $leave ? $leave->type : null;
    }
}
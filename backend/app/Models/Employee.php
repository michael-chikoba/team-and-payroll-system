<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; // Import Builder

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'country_id',
        'user_id',
        'manager_id',
        'employee_id',
        'position',
        'department',
        'base_salary',
        'hire_date',
        'employment_type',
        'bank_details',
        'phone',
        'date_of_birth',
        'national_id',
        'address',
        'emergency_contact',
        'profile_pic',
        'is_active',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'bank_details' => 'array',
        'emergency_contact' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
        'email',
        'formatted_salary',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically assign a manager if not provided during creation
        static::creating(function ($employee) {
            if (empty($employee->manager_id) && $employee->department) {
                $query = Manager::where('department', $employee->department);
                
                // Filter by country if set
                if ($employee->country_id) {
                    $query->where('country_id', $employee->country_id);
                }
                
                // Filter by business if set
                if ($employee->business_id) {
                    $query->where('business_id', $employee->business_id);
                }
                
                $manager = $query->first();
                
                if ($manager) {
                    $employee->manager_id = $manager->user_id;
                }
            }
        });

        // Update manager assignment when department changes
        static::updating(function ($employee) {
            if ($employee->isDirty('department') && empty($employee->manager_id) && $employee->department) {
                $query = Manager::where('department', $employee->department);
                
                // Filter by country if set
                if ($employee->country_id) {
                    $query->where('country_id', $employee->country_id);
                }
                
                // Filter by business if set
                if ($employee->business_id) {
                    $query->where('business_id', $employee->business_id);
                }
                
                $manager = $query->first();
                
                if ($manager) {
                    $employee->manager_id = $manager->user_id;
                }
            }
        });
    }

    /**
     * ========================================
     * RELATIONSHIPS
     * ========================================
     */

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function activeAdvances(): HasMany
    {
        return $this->advances()
            ->where('status', 'active')
            ->where('balance', '>', 0);
    }

    public function activeLoans(): HasMany
    {
        return $this->loans()
            ->where('status', 'active')
            ->where('balance', '>', 0);
    }

    /**
     * ========================================
     * SCOPES
     * ========================================
     */

    public function scopeInBusiness(Builder $query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeInCountry(Builder $query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to filter employees within the same context (Business & Country) as the Manager
     * This fixes the "Call to undefined method" error.
     */
    public function scopeSameContext(Builder $query, Employee $managerProfile)
    {
        return $query->where('business_id', $managerProfile->business_id)
                     ->where('country_id', $managerProfile->country_id);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInDepartment(Builder $query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByEmploymentType(Builder $query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })->orWhere('employee_id', 'like', "%{$search}%");
    }

    public function scopeByBusinessAndCountry(Builder $query, $businessId = null, $countryId = null)
    {
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        
        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        
        return $query;
    }

    /**
     * ========================================
     * ACCESSORS
     * ========================================
     */

    public function getFullNameAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->first_name . ' ' . $this->user->last_name);
        }
        
        if (isset($this->attributes['user_first_name']) && isset($this->attributes['user_last_name'])) {
            return trim($this->attributes['user_first_name'] . ' ' . $this->attributes['user_last_name']);
        }
        
        return 'N/A';
    }

    public function getEmailAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->email;
        }
        
        if (isset($this->attributes['user_email'])) {
            return $this->attributes['user_email'];
        }
        
        return 'N/A';
    }

    public function getFormattedSalaryAttribute(): string
    {
        if (!$this->country) {
            return number_format($this->base_salary, 2);
        }

        return $this->country->formatCurrency($this->base_salary);
    }

    /**
     * ========================================
     * METHODS
     * ========================================
     */

    public function getCurrentLeaveBalance(string $type): float
    {
        $balance = $this->leaveBalances()
            ->where('type', $type)
            ->first();

        return $balance ? $balance->balance : 0;
    }

    public function getAllLeaveBalances(): array
    {
        return $this->leaveBalances()
            ->get()
            ->pluck('balance', 'type')
            ->toArray();
    }

    public function initializeLeaveBalances(): void
    {
        if (!$this->country || !$this->country->configuration) {
            return;
        }

        $config = $this->country->configuration;

        $leaveTypes = [
            'annual' => $config->annual_leave_days,
            'sick' => $config->sick_leave_days,
            'maternity' => $config->maternity_leave_days,
            'paternity' => $config->paternity_leave_days,
        ];

        foreach ($leaveTypes as $type => $days) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $this->id,
                    'type' => $type,
                ],
                [
                    'balance' => $days,
                    'used' => 0,
                    'year' => now()->year,
                ]
            );
        }
    }

    public function hasActiveDeductions(): bool
    {
        return $this->activeAdvances()->exists() || $this->activeLoans()->exists();
    }

    public function getTotalMonthlyDeductions(): float
    {
        $advanceDeductions = $this->activeAdvances()->sum('monthly_deduction');
        $loanDeductions = $this->activeLoans()->sum('monthly_deduction');

        return round($advanceDeductions + $loanDeductions, 2);
    }

    public function getDeductionsBreakdown(): array
    {
        return [
            'advances' => [
                'count' => $this->activeAdvances()->count(),
                'total' => $this->activeAdvances()->sum('monthly_deduction'),
                'items' => $this->activeAdvances()->get(),
            ],
            'loans' => [
                'count' => $this->activeLoans()->count(),
                'total' => $this->activeLoans()->sum('monthly_deduction'),
                'items' => $this->activeLoans()->get(),
            ],
            'total_monthly' => $this->getTotalMonthlyDeductions(),
        ];
    }

    public function belongsToBusiness(int $businessId): bool
    {
        return $this->business_id === $businessId;
    }

    public function belongsToCountry(int $countryId): bool
    {
        return $this->country_id === $countryId;
    }

    public function belongsToBusinessAndCountry(int $businessId, int $countryId): bool
    {
        return $this->business_id === $businessId && $this->country_id === $countryId;
    }

    public function getCountryConfiguration()
    {
        return $this->country ? $this->country->configuration : null;
    }

    public function getWorkingHoursPerDay(): float
    {
        $config = $this->getCountryConfiguration();
        return $config ? $config->working_hours_per_day : 8.00;
    }

    public function getPayrollFrequency(): string
    {
        $config = $this->getCountryConfiguration();
        return $config ? $config->payroll_frequency : 'monthly';
    }

    public function calculateNetSalary(array $additionalDeductions = []): array
    {
        $config = $this->getCountryConfiguration();

        if (!$config) {
            return [
                'gross_salary' => $this->base_salary,
                'net_salary' => $this->base_salary,
                'tax' => 0,
                'deductions' => [],
            ];
        }

        $tax = $config->calculateTax($this->base_salary);
        $statutoryDeductions = $config->calculateStatutoryDeductions($this->base_salary);
        
        $monthlyDeductions = $this->getTotalMonthlyDeductions();
        $additionalDeductions['advances_loans'] = $monthlyDeductions;

        $totalStatutory = array_sum($statutoryDeductions);
        $totalAdditional = array_sum($additionalDeductions);
        $totalDeductions = $tax + $totalStatutory + $totalAdditional;

        $netSalary = $this->base_salary - $totalDeductions;

        return [
            'gross_salary' => round($this->base_salary, 2),
            'tax' => round($tax, 2),
            'statutory_deductions' => array_map(fn($v) => round($v, 2), $statutoryDeductions),
            'additional_deductions' => array_map(fn($v) => round($v, 2), $additionalDeductions),
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2),
        ];
    }

    public function getYearsOfService(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        return $this->hire_date->diffInYears(now());
    }

    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return $this->date_of_birth->diffInYears(now());
    }

    public function isEligibleForRetirement(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= 60;
    }

    public function getProfilePictureUrl(): string
    {
        if ($this->profile_pic) {
            return asset('storage/' . $this->profile_pic);
        }
        return asset('images/default-avatar.png');
    }

    public function getFormattedBankDetails(): array
    {
        if (!is_array($this->bank_details)) {
            return [];
        }

        return [
            'bank_name' => $this->bank_details['bank_name'] ?? 'N/A',
            'account_number' => $this->bank_details['account_number'] ?? 'N/A',
            'account_name' => $this->bank_details['account_name'] ?? 'N/A',
            'branch' => $this->bank_details['branch'] ?? 'N/A',
        ];
    }
}
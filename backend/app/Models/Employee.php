<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasEncryptedFields;

class Employee extends Model
{
    use HasFactory;
    use HasEncryptedFields;

    protected $table = 'employees';

    protected $fillable = [
        'business_id',
        'country_id',
        'user_id',
        'manager_id',
        'employee_id',
        'position',
        'department',
        'base_salary',
        'transport_allowance',
        'lunch_allowance',
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
        // Status / archive fields
        'status',
        'suspended_at',
        'archived_at',
        'reinstated_at',
        'suspended_by',
        'archived_by',
        'reinstated_by',
        'suspension_reason',
        'archive_reason',
        'status_notes',
    ];

    protected $casts = [
        'hire_date'       => 'date',
        'date_of_birth'   => 'date',
        'bank_details'    => 'array',
        'emergency_contact' => 'array',
        'is_active'       => 'boolean',
        'suspended_at'    => 'datetime',
        'archived_at'     => 'datetime',
        'reinstated_at'   => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'email',
        'formatted_salary',
        'total_salary',
    ];

    // ─────────────────────────────────────────────────────────────
    // ENCRYPTED FIELDS
    // ─────────────────────────────────────────────────────────────

    public function getEncryptedFields(): array
    {
        return [
            'phone',
            'national_id',
            'address',
            'emergency_contact',
            'base_salary',
            'transport_allowance',
            'lunch_allowance',
            'bank_details',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // STATUS HELPERS
    // ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    // ─────────────────────────────────────────────────────────────
    // SALARY ACCESSORS
    // ─────────────────────────────────────────────────────────────

    public function getBaseSalaryValueAttribute(): float
    {
        return (float) ($this->base_salary ?? 0);
    }

    public function getTransportAllowanceValueAttribute(): float
    {
        return (float) ($this->transport_allowance ?? 0);
    }

    public function getLunchAllowanceValueAttribute(): float
    {
        return (float) ($this->lunch_allowance ?? 0);
    }

    public function getTotalSalaryAttribute(): float
    {
        return $this->base_salary_value +
               $this->transport_allowance_value +
               $this->lunch_allowance_value;
    }

    public function getFormattedTotalSalaryAttribute(): string
    {
        if (!$this->country) {
            return number_format($this->total_salary, 2);
        }
        return $this->country->formatCurrency($this->total_salary);
    }

    public function getTransportAllowance(): float
    {
        return $this->transport_allowance_value;
    }

    public function getLunchAllowance(): float
    {
        return $this->lunch_allowance_value;
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to', 'user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by', 'user_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id', 'user_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to', 'user_id');
    }

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

    public function suspendedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function archivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function reinstatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reinstated_by');
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

    // ─────────────────────────────────────────────────────────────
    // BOOT
    // ─────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->manager_id) && $employee->department) {
                $query = Manager::where('department', $employee->department);
                if ($employee->country_id)  $query->where('country_id',  $employee->country_id);
                if ($employee->business_id) $query->where('business_id', $employee->business_id);
                $manager = $query->first();
                if ($manager) $employee->manager_id = $manager->user_id;
            }
        });

        static::updating(function ($employee) {
            if ($employee->isDirty('department') && empty($employee->manager_id) && $employee->department) {
                $query = Manager::where('department', $employee->department);
                if ($employee->country_id)  $query->where('country_id',  $employee->country_id);
                if ($employee->business_id) $query->where('business_id', $employee->business_id);
                $manager = $query->first();
                if ($manager) $employee->manager_id = $manager->user_id;
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    public function scopeInBusiness(Builder $query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeInCountry(Builder $query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeSameContext(Builder $query, Employee $managerProfile)
    {
        return $query->where('business_id', $managerProfile->business_id)
                     ->where('country_id',  $managerProfile->country_id);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended(Builder $query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeArchived(Builder $query)
    {
        return $query->where('status', 'archived');
    }

    /** Returns active + suspended (i.e. not archived) */
    public function scopeNotArchived(Builder $query)
    {
        return $query->whereIn('status', ['active', 'suspended']);
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
              ->orWhere('last_name',  'like', "%{$search}%")
              ->orWhere('email',      'like', "%{$search}%");
        })->orWhere('employee_id', 'like', "%{$search}%");
    }

    public function scopeByBusinessAndCountry(Builder $query, $businessId = null, $countryId = null)
    {
        if ($businessId) $query->where('business_id', $businessId);
        if ($countryId)  $query->where('country_id',  $countryId);
        return $query;
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->first_name . ' ' . $this->user->last_name);
        }
        if (isset($this->attributes['user_first_name'], $this->attributes['user_last_name'])) {
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
            return number_format($this->base_salary_value, 2);
        }
        return $this->country->formatCurrency($this->base_salary_value);
    }

    // ─────────────────────────────────────────────────────────────
    // PAYROLL HELPERS
    // ─────────────────────────────────────────────────────────────

    public function getCountryCode(): ?string
    {
        if ($this->country_id && $this->country) return $this->country->code;
        if ($this->business && $this->business->country_code) return $this->business->country_code;
        return null;
    }

    public function resolveCountry()
    {
        if ($this->country_id) return $this->country;
        if ($this->business && $this->business->country) return $this->business->country;
        return Country::where('code', 'ZM')->first();
    }

    public function getTaxConfiguration(): ?TaxConfiguration
    {
        return TaxConfiguration::getForBusinessAndCountry(
            $this->business_id,
            $this->getCountryCode()
        );
    }

    // ─────────────────────────────────────────────────────────────
    // LEAVE MANAGEMENT
    // ─────────────────────────────────────────────────────────────

    public function getCurrentLeaveBalance(string $type): float
    {
        $balance = $this->leaveBalances()->where('type', $type)->first();
        return $balance ? $balance->balance : 0;
    }

    public function getAllLeaveBalances(): array
    {
        return $this->leaveBalances()->get()->pluck('balance', 'type')->toArray();
    }

    public function initializeLeaveBalances(): void
    {
        if (!$this->country || !$this->country->configuration) return;
        $config = $this->country->configuration;
        $leaveTypes = [
            'annual'    => $config->annual_leave_days,
            'sick'      => $config->sick_leave_days,
            'maternity' => $config->maternity_leave_days,
            'paternity' => $config->paternity_leave_days,
        ];
        foreach ($leaveTypes as $type => $days) {
            LeaveBalance::firstOrCreate(
                ['employee_id' => $this->id, 'type' => $type],
                ['balance' => $days, 'used' => 0, 'year' => now()->year]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // DEDUCTIONS
    // ─────────────────────────────────────────────────────────────

    public function hasActiveDeductions(): bool
    {
        return $this->activeAdvances()->exists() || $this->activeLoans()->exists();
    }

    public function getTotalMonthlyDeductions(): float
    {
        return round(
            $this->activeAdvances()->sum('monthly_deduction') +
            $this->activeLoans()->sum('monthly_deduction'),
            2
        );
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

    // ─────────────────────────────────────────────────────────────
    // UTILITIES
    // ─────────────────────────────────────────────────────────────

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
        $config     = $this->getCountryConfiguration();
        $baseSalary = $this->base_salary_value;

        if (!$config) {
            return ['gross_salary' => $baseSalary, 'net_salary' => $baseSalary, 'tax' => 0, 'deductions' => []];
        }

        $tax                 = $config->calculateTax($baseSalary);
        $statutoryDeductions = $config->calculateStatutoryDeductions($baseSalary);
        $monthlyDeductions   = $this->getTotalMonthlyDeductions();
        $additionalDeductions['advances_loans'] = $monthlyDeductions;

        $totalStatutory   = array_sum($statutoryDeductions);
        $totalAdditional  = array_sum($additionalDeductions);
        $totalDeductions  = $tax + $totalStatutory + $totalAdditional;
        $netSalary        = $baseSalary - $totalDeductions;

        return [
            'gross_salary'          => round($baseSalary, 2),
            'tax'                   => round($tax, 2),
            'statutory_deductions'  => array_map(fn($v) => round($v, 2), $statutoryDeductions),
            'additional_deductions' => array_map(fn($v) => round($v, 2), $additionalDeductions),
            'total_deductions'      => round($totalDeductions, 2),
            'net_salary'            => round($netSalary, 2),
        ];
    }

    public function getYearsOfService(): int
    {
        if (!$this->hire_date) return 0;
        return $this->hire_date->diffInYears(now());
    }

    public function getAge(): ?int
    {
        if (!$this->date_of_birth) return null;
        return $this->date_of_birth->diffInYears(now());
    }

    public function isEligibleForRetirement(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= 60;
    }

    public function getProfilePictureUrl(): string
    {
        if ($this->profile_pic) return asset('storage/' . $this->profile_pic);
        return asset('images/default-avatar.png');
    }

    public function getFormattedBankDetails(): array
    {
        $details = $this->bank_details;
        if (!is_array($details)) return [];
        return [
            'bank_name'      => $details['bank_name']      ?? 'N/A',
            'account_number' => $details['account_number'] ?? 'N/A',
            'account_name'   => $details['account_name']   ?? 'N/A',
            'branch'         => $details['branch']         ?? 'N/A',
        ];
    }
}
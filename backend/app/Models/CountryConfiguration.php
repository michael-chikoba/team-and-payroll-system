<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'working_hours_per_day',
        'working_days_per_week',
        'working_days',
        'work_start_time',
        'work_end_time',
        'overtime_multiplier',
        'weekend_multiplier',
        'holiday_multiplier',
        'payroll_frequency',
        'payroll_day',
        'tax_calculation_type',
        'tax_brackets',
        'flat_tax_rate',
        'statutory_deductions',
        'annual_leave_days',
        'sick_leave_days',
        'maternity_leave_days',
        'paternity_leave_days',
        'public_holidays',
        'minimum_wage',
        'compliance_requirements',
    ];

    protected $casts = [
        'working_hours_per_day' => 'decimal:2',
        'working_days_per_week' => 'integer',
        'working_days' => 'array',
        'overtime_multiplier' => 'decimal:2',
        'weekend_multiplier' => 'decimal:2',
        'holiday_multiplier' => 'decimal:2',
        'payroll_day' => 'integer',
        'tax_brackets' => 'array',
        'flat_tax_rate' => 'decimal:2',
        'statutory_deductions' => 'array',
        'annual_leave_days' => 'integer',
        'sick_leave_days' => 'integer',
        'maternity_leave_days' => 'integer',
        'paternity_leave_days' => 'integer',
        'public_holidays' => 'array',
        'compliance_requirements' => 'array',
    ];

    /**
     * Get the country that owns the configuration.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Check if a date is a public holiday.
     */
    public function isPublicHoliday(string $date): bool
    {
        if (!$this->public_holidays) {
            return false;
        }

        $checkDate = date('Y-m-d', strtotime($date));
        
        foreach ($this->public_holidays as $holiday) {
            if (isset($holiday['date']) && $holiday['date'] === $checkDate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get working days as array of integers (1 = Monday, 7 = Sunday).
     */
    public function getWorkingDaysArray(): array
    {
        return $this->working_days ?? [1, 2, 3, 4, 5];
    }

    /**
     * Check if a day is a working day.
     */
    public function isWorkingDay(string $date): bool
    {
        $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
        return in_array($dayOfWeek, $this->getWorkingDaysArray());
    }

    /**
     * Calculate tax based on country's tax system.
     */
    public function calculateTax(float $grossSalary): float
    {
        if ($this->tax_calculation_type === 'none') {
            return 0;
        }

        if ($this->tax_calculation_type === 'flat') {
            return $grossSalary * ($this->flat_tax_rate / 100);
        }

        // Progressive tax calculation
        if ($this->tax_calculation_type === 'progressive' && $this->tax_brackets) {
            $tax = 0;
            $remainingIncome = $grossSalary;

            foreach ($this->tax_brackets as $bracket) {
                $min = $bracket['min'] ?? 0;
                $max = $bracket['max'] ?? PHP_FLOAT_MAX;
                $rate = $bracket['rate'] ?? 0;

                if ($remainingIncome <= 0) {
                    break;
                }

                $taxableInBracket = min($remainingIncome, $max - $min);
                $tax += $taxableInBracket * ($rate / 100);
                $remainingIncome -= $taxableInBracket;
            }

            return $tax;
        }

        return 0;
    }

    /**
     * Get statutory deductions for a given salary.
     */
    public function calculateStatutoryDeductions(float $grossSalary): array
    {
        $deductions = [];

        if (!$this->statutory_deductions) {
            return $deductions;
        }

        foreach ($this->statutory_deductions as $deduction) {
            $name = $deduction['name'] ?? '';
            $type = $deduction['type'] ?? 'percentage'; // percentage or fixed
            $value = $deduction['value'] ?? 0;
            $cap = $deduction['cap'] ?? null;

            if ($type === 'percentage') {
                $amount = $grossSalary * ($value / 100);
                if ($cap && $amount > $cap) {
                    $amount = $cap;
                }
            } else {
                $amount = $value;
            }

            $deductions[$name] = $amount;
        }

        return $deductions;
    }
}
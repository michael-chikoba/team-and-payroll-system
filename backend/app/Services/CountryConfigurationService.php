<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CountryConfiguration;
use Illuminate\Support\Collection;

class CountryConfigurationService
{
    /**
     * Get country configuration by country ID.
     */
    public function getConfiguration(int $countryId): ?CountryConfiguration
    {
        return CountryConfiguration::where('country_id', $countryId)->first();
    }

    /**
     * Get country by ID with configuration.
     */
    public function getCountryWithConfiguration(int $countryId): ?Country
    {
        return Country::with('configuration')->find($countryId);
    }

    /**
     * Calculate working hours for a date range considering country rules.
     */
    public function calculateWorkingHours(int $countryId, string $startDate, string $endDate): float
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config) {
            return 0;
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $totalHours = 0;

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            
            // Skip weekends and public holidays
            if (!$config->isWorkingDay($dateString) || $config->isPublicHoliday($dateString)) {
                continue;
            }

            $totalHours += $config->working_hours_per_day;
        }

        return $totalHours;
    }

    /**
     * Calculate overtime multiplier based on date and time.
     */
    public function getOvertimeMultiplier(int $countryId, string $date): float
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config) {
            return 1.5;
        }

        // Check if it's a public holiday
        if ($config->isPublicHoliday($date)) {
            return $config->holiday_multiplier;
        }

        // Check if it's a weekend
        if (!$config->isWorkingDay($date)) {
            return $config->weekend_multiplier;
        }

        // Regular overtime
        return $config->overtime_multiplier;
    }

    /**
     * Get leave entitlement for country.
     */
    public function getLeaveEntitlement(int $countryId): array
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config) {
            return [
                'annual_leave' => 0,
                'sick_leave' => 0,
                'maternity_leave' => 0,
                'paternity_leave' => 0,
            ];
        }

        return [
            'annual_leave' => $config->annual_leave_days,
            'sick_leave' => $config->sick_leave_days,
            'maternity_leave' => $config->maternity_leave_days,
            'paternity_leave' => $config->paternity_leave_days,
        ];
    }

    /**
     * Calculate gross to net salary for a country.
     */
    public function calculateNetSalary(int $countryId, float $grossSalary, array $additionalDeductions = []): array
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config) {
            return [
                'gross_salary' => $grossSalary,
                'tax' => 0,
                'statutory_deductions' => [],
                'additional_deductions' => $additionalDeductions,
                'total_deductions' => array_sum($additionalDeductions),
                'net_salary' => $grossSalary - array_sum($additionalDeductions),
            ];
        }

        // Calculate tax
        $tax = $config->calculateTax($grossSalary);

        // Calculate statutory deductions
        $statutoryDeductions = $config->calculateStatutoryDeductions($grossSalary);

        // Total deductions
        $totalStatutory = array_sum($statutoryDeductions);
        $totalAdditional = array_sum($additionalDeductions);
        $totalDeductions = $tax + $totalStatutory + $totalAdditional;

        // Net salary
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'gross_salary' => round($grossSalary, 2),
            'tax' => round($tax, 2),
            'statutory_deductions' => array_map(fn($v) => round($v, 2), $statutoryDeductions),
            'additional_deductions' => array_map(fn($v) => round($v, 2), $additionalDeductions),
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2),
        ];
    }

    /**
     * Get public holidays for a country in a given year.
     */
    public function getPublicHolidays(int $countryId, int $year = null): array
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config || !$config->public_holidays) {
            return [];
        }

        $holidays = $config->public_holidays;

        if ($year) {
            $holidays = array_filter($holidays, function($holiday) use ($year) {
                return date('Y', strtotime($holiday['date'])) == $year;
            });
        }

        return array_values($holidays);
    }

    /**
     * Get all active countries with configurations.
     */
    public function getAllActiveCountries(): Collection
    {
        return Country::active()->with('configuration')->get();
    }

    /**
     * Update country configuration.
     */
    public function updateConfiguration(int $countryId, array $data): bool
    {
        $config = $this->getConfiguration($countryId);
        
        if (!$config) {
            return false;
        }

        return $config->update($data);
    }

    /**
     * Format currency for country.
     */
    public function formatCurrency(int $countryId, float $amount): string
    {
        $country = Country::find($countryId);
        
        if (!$country) {
            return number_format($amount, 2);
        }

        return $country->formatCurrency($amount);
    }

    /**
     * Format date for country.
     */
    public function formatDate(int $countryId, string $date): string
    {
        $country = Country::find($countryId);
        
        if (!$country) {
            return date('Y-m-d', strtotime($date));
        }

        return $country->formatDate($date);
    }
}
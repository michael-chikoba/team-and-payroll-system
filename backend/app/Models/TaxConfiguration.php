<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxConfiguration extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'country',
        'state',
        'config_data',
        'is_active',
    ];
    
    protected $casts = [
        'config_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function scopeForLocation($query, $country, $state = null) {
        return $query->where('country', $country)
                     ->when($state, fn($q) => $q->where('state', $state))
                     ->active();
    }

    public function getConfigDataAttribute($value) {
        return json_decode($value, true);
    }

    public function setConfigDataAttribute($value) {
        $this->attributes['config_data'] = json_encode($value);
    }

    /**
     * Calculate pension deduction (5% of basic pay for full-time employees only)
     */
    public function calculatePension(Employee $employee, float $basicSalary): float
    {
        // Only apply pension deduction to full-time employees
        if ($employee->employment_type !== 'full_time') {
            return 0.0;
        }

        $config = $this->config_data;
        $rate = ($config['pensionRate'] ?? 5) / 100;
        $maxSalary = $config['pensionMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($basicSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate progressive PAYE on GROSS salary
     */
    public function calculatePAYE(float $grossSalary): float
    {
        $tax = 0.0;
        $remaining = $grossSalary;
        $bands = $this->config_data['taxBands'] ?? [];
        
        usort($bands, function($a, $b) {
            return ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0);
        });

        foreach ($bands as $band) {
            if ($remaining <= 0) break;
            
            $lower = $band['lowerLimit'] ?? 0;
            $upper = $band['upperLimit'] ?? null;
            $rate = ($band['rate'] ?? 0) / 100;

            if ($upper === null) {
                $tax += $remaining * $rate;
                break;
            }

            $bandWidth = $upper - $lower;
            $taxableInBand = min($remaining, max(0, $bandWidth));
            $tax += $taxableInBand * $rate;
            $remaining -= $taxableInBand;
        }
        
        return $this->applyRounding($tax);
    }

    /**
     * Calculate NAPSA employee contribution (5% of gross, capped)
     */
    public function calculateNAPSA(float $grossSalary): float
    {
        $config = $this->config_data;
        $rate = ($config['napsaRate'] ?? 5) / 100;
        $maxSalary = $config['napsaMaxSalary'] ?? 34164.00;
        $maxContrib = 1708.20;
        
        $assessable = min($grossSalary, $maxSalary);
        $contrib = min($assessable * $rate, $maxContrib);
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate NHIMA employee contribution (1% of basic)
     */
    public function calculateNHIMA(float $basicSalary): float
    {
        $config = $this->config_data;
        $rate = ($config['nhimaEmployeeRate'] ?? 1) / 100;
        $maxSalary = $config['nhimaMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($basicSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate allowances from employee data
     */
    public function calculateAllowances(Employee $employee): array
    {
        $basicSalary = $employee->base_salary;
        $housing = $basicSalary * 0.25;
        $transport = $employee->transport_allowance ?? 0.00;
        $lunch = $employee->lunch_allowance ?? 0.00;
        
        return [
            'housing' => $this->applyRounding($housing),
            'transport' => $this->applyRounding($transport),
            'lunch' => $this->applyRounding($lunch),
            'total' => $this->applyRounding($housing + $transport + $lunch)
        ];
    }

    /**
     * Full payroll calculation with proper breakdown including pension
     */
    public function calculatePayroll(Employee $employee, float $overtimePay = 0, float $bonuses = 0): array
    {
        $basicSalary = $employee->base_salary;
        
        // Calculate allowances
        $allowances = $this->calculateAllowances($employee);
        
        // Calculate gross salary (basic + allowances + overtime + bonuses)
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;
        
        // Calculate deductions
        $paye = $this->calculatePAYE($grossSalary);
        $napsa = $this->calculateNAPSA($grossSalary);
        $nhima = $this->calculateNHIMA($basicSalary);
        $pension = $this->calculatePension($employee, $basicSalary); // New pension deduction
        
        $totalDeductions = $paye + $napsa + $nhima + $pension;
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'overtime_pay' => $overtimePay,
            'bonuses' => $bonuses,
            'gross_salary' => $this->applyRounding($grossSalary),
            'deductions' => [
                'paye_tax' => $paye,
                'napsa_deduction' => $napsa,
                'nhima_deduction' => $nhima,
                'pension_deduction' => $pension, // Added pension
                'total_deductions' => $this->applyRounding($totalDeductions),
            ],
            'net_salary' => $this->applyRounding($netSalary),
            'calculation_notes' => [
                'paye_base' => 'Gross Salary',
                'napsa_base' => 'Gross Salary',
                'nhima_base' => 'Basic Salary',
                'pension_base' => 'Basic Salary (Full-time only)',
                'housing_rate' => '25% of Basic',
                'employment_type' => $employee->employment_type,
                'pension_applied' => $pension > 0
            ]
        ];
    }

    /**
     * Apply rounding method
     */
    private function applyRounding(float $amount): float {
        $method = $this->config_data['roundingMethod'] ?? 'nearest';
        return match($method) {
            'up' => ceil($amount),
            'down' => floor($amount),
            'none' => $amount,
            default => round($amount, 2),
        };
    }
}
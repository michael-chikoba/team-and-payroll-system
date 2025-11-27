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
     * Calculate progressive PAYE on GROSS salary
     */
    public function calculatePAYE(float $grossSalary): float
    {
        $tax = 0.0;
        $remaining = $grossSalary; // Use gross salary for PAYE calculation
        $bands = $this->config_data['taxBands'] ?? [];
        
        // Sort bands by lower limit
        usort($bands, function($a, $b) {
            return ($a['lowerLimit'] ?? 0) <=> ($b['lowerLimit'] ?? 0);
        });

        foreach ($bands as $band) {
            if ($remaining <= 0) break;
            
            $lower = $band['lowerLimit'] ?? 0;
            $upper = $band['upperLimit'] ?? null;
            $rate = ($band['rate'] ?? 0) / 100;

            if ($upper === null) {
                // Unlimited upper - last band
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
        $maxContrib = 1708.20; // 2025 hardcoded cap for employee
        
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
        $housing = $basicSalary * 0.25; // 25% of basic
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
     * Full payroll calculation with proper breakdown
     */
    public function calculatePayroll(Employee $employee, float $overtimePay = 0, float $bonuses = 0): array
    {
        $basicSalary = $employee->base_salary;
        // Calculate allowances
        $allowances = $this->calculateAllowances($employee);
        
        // Calculate gross salary (basic + allowances + overtime + bonuses)
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;
        
        // Calculate deductions
        $paye = $this->calculatePAYE($grossSalary); // PAYE from gross
        $napsa = $this->calculateNAPSA($grossSalary); // NAPSA from gross
        $nhima = $this->calculateNHIMA($basicSalary); // NHIMA from basic
        
        $totalDeductions = $paye + $napsa + $nhima;
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
                'total_deductions' => $this->applyRounding($totalDeductions),
            ],
            'net_salary' => $this->applyRounding($netSalary),
            'calculation_notes' => [
                'paye_base' => 'Gross Salary',
                'napsa_base' => 'Gross Salary',
                'nhima_base' => 'Basic Salary',
                'housing_rate' => '25% of Basic'
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
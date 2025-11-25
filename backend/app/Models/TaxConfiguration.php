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
     * Calculate progressive PAYE on BASIC salary only (not gross)
     */
    public function calculatePAYE(float $basicSalary): float
    {
        $tax = 0.0;
        $remaining = $basicSalary; // Use basic salary for PAYE calculation
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
     * Calculate NHIMA employee contribution (1% of gross)
     */
    public function calculateNHIMA(float $grossSalary): float
    {
        $config = $this->config_data;
        $rate = ($config['nhimaEmployeeRate'] ?? 1) / 100;
        $maxSalary = $config['nhimaMaxSalary'] ?? PHP_FLOAT_MAX;
        
        $base = min($grossSalary, $maxSalary);
        $contrib = $base * $rate;
        
        return $this->applyRounding($contrib);
    }

    /**
     * Calculate standard allowances based on basic pay
     * Housing: 25% of basic, Transport: K300, Lunch: K240
     */
    public function calculateAllowances(float $basicSalary): array
    {
        $housing = $basicSalary * 0.25; // 25% of basic
        $transport = 300.00; // Fixed K300
        $lunch = 240.00; // Fixed K240
        
        return [
            'housing' => $this->applyRounding($housing),
            'transport' => $transport,
            'lunch' => $lunch,
            'total' => $this->applyRounding($housing + $transport + $lunch)
        ];
    }

    /**
     * Full payroll calculation with proper breakdown
     */
    public function calculatePayroll(float $basicSalary, float $overtimePay = 0, float $bonuses = 0): array
    {
        // Calculate allowances
        $allowances = $this->calculateAllowances($basicSalary);
        
        // Calculate gross salary (basic + allowances + overtime + bonuses)
        $grossSalary = $basicSalary + $allowances['total'] + $overtimePay + $bonuses;
        
        // Calculate deductions
        $paye = $this->calculatePAYE($basicSalary); // PAYE from basic only
        $napsa = $this->calculateNAPSA($grossSalary); // NAPSA from gross
        $nhima = $this->calculateNHIMA($grossSalary); // NHIMA from gross
        
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
                'paye_base' => 'Basic Salary',
                'napsa_base' => 'Gross Salary', 
                'nhima_base' => 'Gross Salary',
                'housing_rate' => '25% of Basic',
                'transport_fixed' => 'K300',
                'lunch_fixed' => 'K240'
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
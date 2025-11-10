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
        'tax_brackets',
        'social_security_rate',
        'medicare_rate',
        'is_active',
    ];

    protected $casts = [
        'tax_brackets' => 'array',
        'social_security_rate' => 'decimal:3',
        'medicare_rate' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLocation($query, $country, $state = null)
    {
        return $query->where('country', $country)
                    ->where('state', $state)
                    ->active();
    }

    public function getTaxBracketsAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setTaxBracketsAttribute($value)
    {
        $this->attributes['tax_brackets'] = json_encode($value);
    }

    public function calculateTax($income)
    {
        $tax = 0;
        $remainingIncome = $income;

        foreach ($this->tax_brackets as $bracket) {
            if ($remainingIncome <= 0) break;

            $bracketMax = $bracket['max'] ?? null;
            $bracketMin = $bracket['min'] ?? 0;
            $rate = $bracket['rate'];

            if ($bracketMax === null) {
                // Last bracket - tax all remaining income
                $tax += $remainingIncome * $rate;
                break;
            }

            $bracketRange = $bracketMax - $bracketMin;
            $taxableInBracket = min($remainingIncome, $bracketRange);
            $tax += $taxableInBracket * $rate;
            $remainingIncome -= $taxableInBracket;
        }

        return $tax;
    }
}
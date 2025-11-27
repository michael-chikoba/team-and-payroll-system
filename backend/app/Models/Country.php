<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'currency_code',
        'currency_symbol',
        'date_format',
        'time_format',
        'timezone',
        'phone_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the configuration for the country.
     */
    public function configuration(): HasOne
    {
        return $this->hasOne(CountryConfiguration::class);
    }

    /**
     * Get all employees in this country.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all attendances in this country.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get all leaves in this country.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Get all payrolls in this country.
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get all payslips in this country.
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Scope to get only active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get country by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Format currency for this country.
     */
    public function formatCurrency(float $amount): string
    {
        return $this->currency_symbol . number_format($amount, 2);
    }

    /**
     * Format date for this country.
     */
    public function formatDate(string $date): string
    {
        return date($this->date_format, strtotime($date));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // Valid leave types - must match config and database
    public const VALID_TYPES = [
        'annual',
        'sick',
        'maternity',
        'paternity',
        'bereavement',
        'unpaid',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Deduct days from balance
     */
    public function deduct(float $days): bool
    {
        if ($this->balance < $days) {
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
        $this->balance = $this->allocated_days + $this->carried_over - $this->used_days;
        
        return $this->save();
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
}
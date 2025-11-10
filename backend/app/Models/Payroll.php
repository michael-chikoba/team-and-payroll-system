<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period',
        'start_date',
        'end_date',
        'status',
        'total_gross',
        'total_net',
        'employee_count',
        'processed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_net' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->processed_at = now();
        return $this->save();
    }

    public function markAsProcessing()
    {
        $this->status = 'processing';
        return $this->save();
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }
}
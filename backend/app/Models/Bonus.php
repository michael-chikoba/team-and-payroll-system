<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'type',
        'description',
        'amount',
        'calculation_basis',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }

    public function scopePerformanceBased($query)
    {
        return $query->where('type', 'performance');
    }

    public function scopeAttendanceBased($query)
    {
        return $query->where('type', 'attendance');
    }

    public function scopeSpecial($query)
    {
        return $query->where('type', 'special');
    }
}
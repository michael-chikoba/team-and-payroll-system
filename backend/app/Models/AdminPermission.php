<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPermission extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'cannot_add_employee',
        'cannot_view_payroll',
        'cannot_view_payslip',
        'cannot_manage_admins',
        'is_suspended',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
    ];

    protected $casts = [
        'cannot_add_employee'  => 'boolean',
        'cannot_view_payroll'  => 'boolean',
        'cannot_view_payslip'  => 'boolean',
        'cannot_manage_admins' => 'boolean',
        'is_suspended'         => 'boolean',
        'suspended_at'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_count',
        'message',
        'status',
        'contacted_at'
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];
}
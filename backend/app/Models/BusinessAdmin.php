<?php
// app/Models/BusinessAdmin.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOwners($query)
    {
        return $query->where('role', 'owner');
    }
}
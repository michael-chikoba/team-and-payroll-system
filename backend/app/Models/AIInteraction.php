<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIInteraction extends Model
{
    protected $fillable = [
        'user_id', 'question', 'answer', 'provider', 
        'is_system_related', 'response_time_ms'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
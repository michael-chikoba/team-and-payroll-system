<?php
// app/Models/LearningModuleProgress.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningModuleProgress extends Model
{
    protected $table = 'learning_module_progress';
    
    protected $fillable = [
        'enrollment_id',
        'module_id',
        'completed',
        'completed_at'
    ];
    
    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime'
    ];
    
    public function enrollment()
    {
        return $this->belongsTo(LearningEnrollment::class, 'enrollment_id');
    }
    
    public function module()
    {
        return $this->belongsTo(LearningModule::class, 'module_id');
    }
}
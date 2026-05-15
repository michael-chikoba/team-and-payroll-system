<?php
// app/Models/LearningModule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    protected $table = 'learning_modules';
    
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'type',
        'content',
        'order',
        'duration_minutes'
    ];
    
    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer'
    ];
    
    public function course()
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }
    
    public function progress()
    {
        return $this->hasMany(LearningModuleProgress::class, 'module_id');
    }
    
    public function isCompletedByEnrollment($enrollmentId)
    {
        return $this->progress()
            ->where('enrollment_id', $enrollmentId)
            ->where('completed', true)
            ->exists();
    }
}
<?php
// app/Models/LearningAssessment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningAssessment extends Model
{
    protected $table = 'learning_assessments';
    
    protected $fillable = [
        'course_id',
        'title',
        'pass_mark',
        'max_attempts',
        'time_limit_minutes'
    ];
    
    protected $casts = [
        'pass_mark' => 'integer',
        'max_attempts' => 'integer',
        'time_limit_minutes' => 'integer'
    ];
    
    public function course()
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }
    
    public function questions()
    {
        return $this->hasMany(LearningQuestion::class, 'assessment_id')->orderBy('order');
    }
    
    public function attempts()
    {
        return $this->hasMany(LearningAttempt::class, 'assessment_id');
    }
    
    public function getRemainingAttempts($enrollmentId)
    {
        $attemptCount = $this->attempts()
            ->whereHas('enrollment', function($q) use ($enrollmentId) {
                $q->where('id', $enrollmentId);
            })->count();
        
        return max(0, $this->max_attempts - $attemptCount);
    }
    
    public function hasPassed($enrollmentId)
    {
        return $this->attempts()
            ->whereHas('enrollment', function($q) use ($enrollmentId) {
                $q->where('id', $enrollmentId);
            })
            ->where('passed', true)
            ->exists();
    }
}
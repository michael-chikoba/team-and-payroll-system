<?php
// app/Models/LearningAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningAttempt extends Model
{
    protected $table = 'learning_attempts';
    
    protected $fillable = [
        'enrollment_id',
        'assessment_id',
        'score',
        'passed',
        'answers',
        'started_at',
        'completed_at'
    ];
    
    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    public function enrollment()
    {
        return $this->belongsTo(LearningEnrollment::class, 'enrollment_id');
    }
    
    public function assessment()
    {
        return $this->belongsTo(LearningAssessment::class, 'assessment_id');
    }
    
    public function isTimeExpired()
    {
        if (!$this->assessment->time_limit_minutes) {
            return false;
        }
        
        $timeLimitSeconds = $this->assessment->time_limit_minutes * 60;
        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        
        return $elapsedSeconds > $timeLimitSeconds;
    }
    
    public function getRemainingTime()
    {
        if (!$this->assessment->time_limit_minutes || $this->completed_at) {
            return null;
        }
        
        $timeLimitSeconds = $this->assessment->time_limit_minutes * 60;
        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        
        return max(0, $timeLimitSeconds - $elapsedSeconds);
    }
}
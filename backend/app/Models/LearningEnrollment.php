<?php
// app/Models/LearningEnrollment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningEnrollment extends Model
{
    protected $table = 'learning_enrollments';
    
    protected $fillable = [
        'course_id',
        'employee_id',
        'status',
        'progress_percent',
        'enrolled_at',
        'completed_at',
        'enrolled_by'
    ];
    
    protected $casts = [
        'progress_percent' => 'integer',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    public function course()
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function enrolledBy()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }
    
    public function moduleProgress()
    {
        return $this->hasMany(LearningModuleProgress::class, 'enrollment_id');
    }
    
    public function attempts()
    {
        return $this->hasMany(LearningAttempt::class, 'enrollment_id');
    }
    
    public function recalculateProgress(): void
    {
        $total = $this->course->modules()->count();
        if ($total === 0) {
            $this->update(['progress_percent' => 0]);
            return;
        }
        
        $done = $this->moduleProgress()->where('completed', true)->count();
        $progress = (int) round($done / $total * 100);
        
        $this->update(['progress_percent' => $progress]);
        
        // Update status based on progress
        if ($progress >= 100 && $this->status !== 'completed') {
            $this->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        } elseif ($progress > 0 && $this->status === 'enrolled') {
            $this->update(['status' => 'in_progress']);
        }
    }
    
    public function getLatestAttempt()
    {
        return $this->attempts()->latest()->first();
    }
    
    public function getCertificateData()
    {
        $passedAttempt = $this->attempts()->where('passed', true)->first();
        if (!$passedAttempt) {
            return null;
        }
        
        return [
            'certificate_id' => 'CERT-' . strtoupper(uniqid()),
            'employee_name' => $this->employee->user->name,
            'course_title' => $this->course->title,
            'course_description' => $this->course->description,
            'score' => $passedAttempt->score,
            'passing_score' => $passedAttempt->assessment->pass_mark,
            'completed_at' => $this->completed_at,
            'issued_at' => now()
        ];
    }
}
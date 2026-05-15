<?php
// app/Models/LearningCourse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningCourse extends Model
{
    use SoftDeletes;
    
    protected $table = 'learning_courses';
    
    protected $fillable = [
        'title',
        'description',
        'category',
        'thumbnail',
        'status',
        'created_by',
        'business_id',
        'assigned_departments',
        'assigned_roles',
        'allow_self_enroll',
        'estimated_minutes'
    ];
    
    protected $casts = [
        'assigned_departments' => 'array',
        'assigned_roles' => 'array',
        'allow_self_enroll' => 'boolean',
        'estimated_minutes' => 'integer'
    ];
    
    public function modules()
    {
        return $this->hasMany(LearningModule::class, 'course_id')->orderBy('order');
    }
    
    public function assessment()
    {
        return $this->hasOne(LearningAssessment::class, 'course_id');
    }
    
    public function enrollments()
    {
        return $this->hasMany(LearningEnrollment::class, 'course_id');
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    
    public function getEnrollmentStatusForEmployee($employeeId)
    {
        $enrollment = $this->enrollments()->where('employee_id', $employeeId)->first();
        if (!$enrollment) {
            return ['enrolled' => false, 'status' => null, 'progress' => 0];
        }
        return [
            'enrolled' => true,
            'status' => $enrollment->status,
            'progress' => $enrollment->progress_percent
        ];
    }
}
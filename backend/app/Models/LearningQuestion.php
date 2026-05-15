<?php
// app/Models/LearningQuestion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningQuestion extends Model
{
    protected $table = 'learning_questions';
    
    protected $fillable = [
        'assessment_id',
        'question',
        'order',
        'points'
    ];
    
    protected $casts = [
        'order' => 'integer',
        'points' => 'integer'
    ];
    
    public function assessment()
    {
        return $this->belongsTo(LearningAssessment::class, 'assessment_id');
    }
    
    public function options()
    {
        return $this->hasMany(LearningQuestionOption::class, 'question_id')->orderBy('order');
    }
    
    public function correctOption()
    {
        return $this->hasOne(LearningQuestionOption::class, 'question_id')->where('is_correct', true);
    }
    
    public function validateAnswer($optionId)
    {
        $correctOption = $this->correctOption;
        return $correctOption && $correctOption->id == $optionId;
    }
}
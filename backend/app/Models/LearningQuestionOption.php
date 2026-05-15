<?php
// app/Models/LearningQuestionOption.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningQuestionOption extends Model
{
    protected $table = 'learning_question_options';
    
    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct',
        'order'
    ];
    
    protected $casts = [
        'is_correct' => 'boolean',
        'order' => 'integer'
    ];
    
    public function question()
    {
        return $this->belongsTo(LearningQuestion::class, 'question_id');
    }
}
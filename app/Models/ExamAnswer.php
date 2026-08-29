<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'exam_attempt_id', 'exam_attempt_question_id', 'answer_payload', 'answered_at'];

    protected $casts = ['answer_payload' => 'array', 'answered_at' => 'datetime'];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttemptQuestion extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'exam_attempt_id', 'question_version_id', 'question_type', 'question_text', 'marks', 'sort_order', 'options_snapshot'];

    protected $casts = ['options_snapshot' => 'array'];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function answer()
    {
        return $this->hasOne(ExamAnswer::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExamPaperQuestion extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'exam_paper_id', 'question_version_id', 'ordinal', 'marks'];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function version()
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id');
    }
}

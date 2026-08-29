<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultItem extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'result_id', 'subject_id', 'exam_schedule_id', 'obtained_marks', 'maximum_marks', 'percentage', 'source', 'letter_grade', 'grade_point', 'is_pass', 'grade_rule_id'];

    protected $casts = ['percentage' => 'decimal:2'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }
}

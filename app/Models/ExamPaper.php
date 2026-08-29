<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'exam_schedule_id', 'version', 'total_marks'];

    public function schedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function questions()
    {
        return $this->hasMany(ExamPaperQuestion::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'exam_schedule_id', 'student_id', 'enrollment_id', 'teacher_id', 'entered_by', 'marks', 'maximum_marks', 'entered_at'];

    protected $casts = ['entered_at' => 'datetime'];

    public function schedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}

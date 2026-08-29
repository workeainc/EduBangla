<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'exam_id', 'academic_year_id', 'subject_id', 'class_id', 'section_id', 'group_id', 'subject_assignment_id', 'teacher_assignment_id', 'teacher_id', 'scheduled_date', 'start_time', 'end_time', 'maximum_marks', 'duration_minutes', 'mode'];

    protected $casts = ['scheduled_date' => 'date'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function teacherAssignment()
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    public function examPaper()
    {
        return $this->hasOne(ExamPaper::class, 'exam_schedule_id');
    }

    public function subjectAssignment()
    {
        return $this->belongsTo(SubjectAssignment::class);
    }
}

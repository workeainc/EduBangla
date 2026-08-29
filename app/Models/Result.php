<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'exam_id', 'student_id', 'enrollment_id', 'status', 'total_obtained', 'total_marks', 'percentage', 'computed_at', 'published_at', 'gpa', 'total_grade_points', 'graded_subject_count', 'overall_status'];

    protected $casts = ['percentage' => 'decimal:2', 'gpa' => 'decimal:2', 'total_grade_points' => 'decimal:2', 'computed_at' => 'datetime', 'published_at' => 'datetime'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function items()
    {
        return $this->hasMany(ResultItem::class);
    }
}

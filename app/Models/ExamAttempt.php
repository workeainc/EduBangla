<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'exam_id', 'student_id', 'enrollment_id', 'attempt_number', 'status', 'started_at', 'expires_at', 'submitted_at', 'finalized_at'];

    protected $casts = ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime', 'finalized_at' => 'datetime'];

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

    public function questions()
    {
        return $this->hasMany(ExamAttemptQuestion::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function isActive()
    {
        return $this->status === 'in_progress';
    }
}

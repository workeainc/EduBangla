<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'result_id', 'student_id', 'enrollment_id', 'exam_id', 'status', 'gpa', 'overall_status', 'snapshot', 'published_at'];

    protected $casts = ['gpa' => 'decimal:2', 'snapshot' => 'array', 'published_at' => 'datetime'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}

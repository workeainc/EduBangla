<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'exam_type_id', 'name', 'code', 'description', 'status', 'created_by', 'locked_at', 'published_at'];

    protected $casts = ['locked_at' => 'datetime', 'published_at' => 'datetime'];

    public function schedules()
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function isLocked()
    {
        return in_array($this->status, ['locked', 'published'], true);
    }
}

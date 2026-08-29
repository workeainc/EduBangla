<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'student_id', 'source_enrollment_id', 'source_class_id', 'source_section_id', 'target_academic_year_id', 'target_class_id', 'target_section_id', 'status', 'decision', 'eligibility_basis', 'decided_by', 'decided_at', 'target_enrollment_id'];

    protected $casts = ['eligibility_basis' => 'array', 'decided_at' => 'datetime'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sourceEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'source_enrollment_id');
    }

    public function targetEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'target_enrollment_id');
    }

    public function targetAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    public function targetClass()
    {
        return $this->belongsTo(AcademicClass::class, 'target_class_id');
    }

    public function targetSection()
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }
}

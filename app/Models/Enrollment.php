<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'student_id', 'academic_year_id', 'class_id', 'section_id', 'group_id', 'group_scope', 'roll', 'status', 'enrolled_at'];

    protected function casts(): array
    {
        return ['enrolled_at' => 'date'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

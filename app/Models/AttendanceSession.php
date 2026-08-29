<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'class_id', 'section_id', 'teacher_id', 'teacher_assignment_id', 'attendance_date', 'period', 'status', 'created_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'finalized_at' => 'datetime', 'status' => 'string'];
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

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function teacherAssignment()
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}

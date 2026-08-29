<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'student_attendance';

    protected $fillable = ['school_id', 'attendance_session_id', 'student_id', 'enrollment_id', 'status', 'recorded_at', 'recorded_by', 'remarks'];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'status' => 'string'];
    }

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
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

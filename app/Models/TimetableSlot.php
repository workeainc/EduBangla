<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetableSlot extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'timetable_id', 'teacher_assignment_id', 'subject_assignment_id', 'teacher_id', 'academic_year_id', 'class_id', 'section_id', 'group_id', 'weekday', 'starts_at', 'ends_at', 'snapshot'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'snapshot' => 'array'];
    }

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function teacherAssignment()
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    public function subjectAssignment()
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $slot) {
            $immutable = array_diff_key($slot->getDirty(), array_flip(['updated_at']));
            if ($immutable !== [] && $slot->timetable()->value('status') !== 'draft') {
                throw new \RuntimeException('Published timetable slots are immutable.');
            }
        });
        static::deleting(function (self $slot) {
            if ($slot->timetable()->value('status') !== 'draft') {
                throw new \RuntimeException('Published timetable slots cannot be deleted.');
            }
        });
    }
}

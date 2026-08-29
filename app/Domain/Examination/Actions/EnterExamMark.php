<?php

namespace App\Domain\Examination\Actions;

use App\Models\Enrollment;
use App\Models\ExamMark;
use App\Models\ExamSchedule;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterExamMark
{
    public function handle(array $d): ExamMark
    {
        return DB::transaction(function () use ($d) {
            $s = ExamSchedule::with('exam')->findOrFail($d['exam_schedule_id']);
            $e = Enrollment::findOrFail($d['enrollment_id']);
            $ta = TeacherAssignment::findOrFail($s->teacher_assignment_id);
            if ($s->school_id != $d['school_id'] || $e->school_id != $s->school_id || $e->academic_year_id != $s->academic_year_id || $e->class_id != $s->class_id || $e->section_id != $s->section_id || $ta->teacher_id != $d['teacher_id'] || $s->exam->isLocked()) {
                throw ValidationException::withMessages(['mark' => 'Mark scope is invalid or locked.']);
            }if ($d['marks'] < 0 || $d['marks'] > $s->maximum_marks) {
                throw ValidationException::withMessages(['marks' => 'Marks exceed maximum.']);
            }

            return ExamMark::updateOrCreate(['exam_schedule_id' => $s->id, 'student_id' => $e->student_id], $d + ['school_id' => $s->school_id, 'entered_at' => now()]);
        });
    }
}

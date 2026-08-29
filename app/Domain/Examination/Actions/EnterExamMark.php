<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
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
            if ($s->school_id != $d['school_id'] || $e->school_id != $s->school_id || $e->student_id != $d['student_id'] || $e->academic_year_id != $s->academic_year_id || $e->class_id != $s->class_id || $e->section_id != $s->section_id || ($s->group_id !== null && $e->group_id != $s->group_id) || $ta->teacher_id != $d['teacher_id'] || $ta->academic_year_id != $s->academic_year_id || $ta->class_id != $s->class_id || $ta->section_id != $s->section_id || $ta->subject_assignment_id != $s->subject_assignment_id || $s->exam->isLocked()) {
                throw ValidationException::withMessages(['mark' => 'Mark scope is invalid or locked.']);
            }if ($d['marks'] < 0 || $d['marks'] > $s->maximum_marks) {
                throw ValidationException::withMessages(['marks' => 'Marks exceed maximum.']);
            }

            $mark = ExamMark::updateOrCreate(['exam_schedule_id' => $s->id, 'student_id' => $e->student_id], $d + ['school_id' => $s->school_id, 'entered_at' => now()]);
            if ($actor = auth()->user()) {
                app(RecordAudit::class)->handle($actor, $s->school_id, 'exam.mark_entered', $mark, null, ['marks' => $mark->marks, 'student_id' => $mark->student_id]);
            }

            return $mark;
        });
    }
}

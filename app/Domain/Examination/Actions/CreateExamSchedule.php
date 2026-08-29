<?php

namespace App\Domain\Examination\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateExamSchedule
{
    public function handle(array $d): ExamSchedule
    {
        return DB::transaction(function () use ($d) {
            $ids = [Exam::class => 'exam_id', AcademicYear::class => 'academic_year_id', AcademicClass::class => 'class_id', Section::class => 'section_id', Subject::class => 'subject_id', SubjectAssignment::class => 'subject_assignment_id', TeacherAssignment::class => 'teacher_assignment_id', Teacher::class => 'teacher_id'];
            $m = [];
            foreach ($ids as $c => $k) {
                $m[$k] = $c::findOrFail($d[$k]);
                if ($m[$k]->school_id != $d['school_id']) {
                    throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
                }
            }
            $ta = $m['teacher_assignment_id'];
            $sa = $m['subject_assignment_id'];
            if ($m['section_id']->class_id != $m['class_id']->id || $m['exam_id']->academic_year_id != $m['academic_year_id']->id || $ta->teacher_id != $m['teacher_id']->id || $ta->subject_assignment_id != $sa->id || $ta->academic_year_id != $m['academic_year_id']->id || $ta->class_id != $m['class_id']->id || $ta->section_id != $m['section_id']->id || $sa->academic_year_id != $m['academic_year_id']->id || $sa->class_id != $m['class_id']->id || $sa->subject_id != $m['subject_id']->id || (($d['group_id'] ?? null) != $ta->group_id && $ta->group_id !== null)) {
                throw ValidationException::withMessages(['schedule' => 'Invalid academic assignment scope.']);
            }

            $scheduleId = $d['_schedule_id'] ?? null;
            unset($d['_schedule_id']);
            $duplicate = ExamSchedule::where('school_id', $d['school_id'])->where('exam_id', $d['exam_id'])->where('class_id', $d['class_id'])->where('section_id', $d['section_id'])->where('subject_assignment_id', $d['subject_assignment_id'])->when($scheduleId, fn ($q) => $q->where('id', '!=', $scheduleId))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['schedule' => 'একই scope-এর schedule ইতোমধ্যে আছে।']);
            }
            if ($scheduleId) {
                $schedule = ExamSchedule::where('school_id', $d['school_id'])->findOrFail($scheduleId);
                if ($schedule->exam->isLocked()) {
                    throw ValidationException::withMessages(['schedule' => 'Locked exam schedule পরিবর্তন করা যাবে না।']);
                }
                $schedule->update($d);

                return $schedule->refresh();
            }

            return ExamSchedule::create($d);
        });
    }
}

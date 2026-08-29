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

            return ExamSchedule::create($d);
        });
    }
}

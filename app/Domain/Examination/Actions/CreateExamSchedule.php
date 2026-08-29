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
            }if ($m['section_id']->class_id != $m['class_id']->id || $m['exam_id']->academic_year_id != $m['academic_year_id']->id || $m['teacher_assignment_id']->teacher_id != $m['teacher_id']->id || $m['teacher_assignment_id']->subject_assignment_id != $m['subject_assignment_id']->id) {
                throw ValidationException::withMessages(['schedule' => 'Invalid academic assignment scope.']);
            }

            return ExamSchedule::create($d);
        });
    }
}

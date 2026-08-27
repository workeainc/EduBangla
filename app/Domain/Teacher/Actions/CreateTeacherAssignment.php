<?php

namespace App\Domain\Teacher\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTeacherAssignment
{
    public function handle(array $data): TeacherAssignment
    {
        return DB::transaction(function () use ($data) {
            $school = (int) $data['school_id'];
            $teacher = Teacher::findOrFail($data['teacher_id']);
            $year = AcademicYear::findOrFail($data['academic_year_id']);
            $class = AcademicClass::findOrFail($data['class_id']);
            $section = Section::findOrFail($data['section_id']);
            $subject = SubjectAssignment::findOrFail($data['subject_assignment_id']);
            foreach ([$teacher, $year, $class, $section, $subject] as $m) {
                if ($m->school_id !== $school) {
                    throw ValidationException::withMessages(['school_id' => 'Invalid tenant relationship.']);
                }
            }if ($section->class_id !== $class->id) {
                throw ValidationException::withMessages(['section_id' => 'Section is not in selected class.']);
            }if ($subject->academic_year_id !== $year->id || $subject->class_id !== $class->id) {
                throw ValidationException::withMessages(['subject_assignment_id' => 'Subject assignment is not valid for selected year/class.']);
            }if (! empty($data['group_id'])) {
                $g = AcademicGroup::findOrFail($data['group_id']);
                if ($g->school_id !== $school || ! ClassGroup::where(['school_id' => $school, 'class_id' => $class->id, 'group_id' => $g->id])->exists() || $subject->group_scope !== $g->id) {
                    throw ValidationException::withMessages(['group_id' => 'Invalid group assignment.']);
                }
            }$data['group_scope'] = $data['group_id'] ?? 0;

            return TeacherAssignment::create($data);
        });
    }
}

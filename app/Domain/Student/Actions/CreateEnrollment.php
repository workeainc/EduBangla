<?php

namespace App\Domain\Student\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEnrollment
{
    public function handle(array $data): Enrollment
    {
        return DB::transaction(function () use ($data) {
            $schoolId = (int) $data['school_id'];
            $student = Student::findOrFail($data['student_id']);
            $year = AcademicYear::findOrFail($data['academic_year_id']);
            $class = AcademicClass::findOrFail($data['class_id']);
            $section = Section::findOrFail($data['section_id']);
            $this->sameSchool($schoolId, $student, $year, $class, $section);
            if ($section->class_id !== $class->id) {
                throw ValidationException::withMessages(['section_id' => 'Section must belong to the selected class.']);
            } if (! empty($data['group_id'])) {
                $this->sameSchool($schoolId, AcademicGroup::findOrFail($data['group_id']));
            } if (Enrollment::forSchool($schoolId)->where('student_id', $data['student_id'])->where('academic_year_id', $data['academic_year_id'])->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['student_id' => 'Student already has an active enrollment for this academic year.']);
            } $data['group_scope'] = $data['group_id'] ?? 0;

            return Enrollment::create($data);
        });
    }

    private function sameSchool(int $schoolId, ...$models): void
    {
        foreach ($models as $model) {
            if ((int) $model->school_id !== $schoolId) {
                throw ValidationException::withMessages(['school_id' => 'All enrollment records must belong to the active school.']);
            }
        }
    }
}

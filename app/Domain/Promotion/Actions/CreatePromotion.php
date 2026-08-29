<?php

namespace App\Domain\Promotion\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Validation\ValidationException;

class CreatePromotion
{
    public function handle(array $data, int $schoolId): Promotion
    {
        $this->validateScope($data, $schoolId);

        return Promotion::create($data + ['school_id' => $schoolId, 'status' => 'draft']);
    }

    public function validateScope(array $data, int $schoolId): void
    {
        $student = Student::where(['id' => $data['student_id'] ?? 0, 'school_id' => $schoolId])->first();
        $source = Enrollment::where(['id' => $data['source_enrollment_id'] ?? 0, 'school_id' => $schoolId, 'student_id' => $data['student_id'] ?? 0, 'status' => 'active'])->first();
        $year = AcademicYear::where(['id' => $data['academic_year_id'] ?? 0, 'school_id' => $schoolId])->first();
        $targetYear = AcademicYear::where(['id' => $data['target_academic_year_id'] ?? 0, 'school_id' => $schoolId])->first();
        $sourceClass = AcademicClass::where(['id' => $data['source_class_id'] ?? 0, 'school_id' => $schoolId])->first();
        $targetClass = AcademicClass::where(['id' => $data['target_class_id'] ?? 0, 'school_id' => $schoolId])->first();
        $sourceSectionValid = Section::where(['id' => $data['source_section_id'] ?? 0, 'school_id' => $schoolId, 'class_id' => $data['source_class_id'] ?? 0])->exists();
        $targetSectionValid = ! ($data['target_section_id'] ?? null) || Section::where(['id' => $data['target_section_id'], 'school_id' => $schoolId, 'class_id' => $data['target_class_id']])->exists();
        if (! $student || ! $source || ! $year || ! $targetYear || ! $sourceClass || ! $targetClass || ! $sourceSectionValid || ! $targetSectionValid
            || $source->academic_year_id !== $year->id || $source->class_id !== $sourceClass->id
            || $source->section_id !== ($data['source_section_id'] ?? null)) {
            throw ValidationException::withMessages(['promotion' => 'Invalid promotion academic scope.']);
        }
    }
}

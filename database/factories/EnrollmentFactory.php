<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */ class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'student_id' => Student::factory(), 'academic_year_id' => AcademicYear::factory(), 'class_id' => AcademicClass::factory(), 'section_id' => Section::factory(), 'group_scope' => 0, 'roll' => 1, 'status' => 'active', 'enrolled_at' => '2026-01-01'];
    }
}

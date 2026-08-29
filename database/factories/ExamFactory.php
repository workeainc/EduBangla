<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamType;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'academic_year_id' => AcademicYear::factory(), 'exam_type_id' => ExamType::factory(), 'name' => $this->faker->words(2, true), 'code' => strtoupper($this->faker->unique()->lexify('EX??')), 'status' => 'draft', 'created_by' => User::factory()];
    }
}

<?php

namespace Database\Factories;

use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionBankFactory extends Factory
{
    protected $model = QuestionBank::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'subject_id' => Subject::factory(), 'name' => $this->faker->words(2, true), 'language' => 'bn', 'status' => 'active'];
    }
}

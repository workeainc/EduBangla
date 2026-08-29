<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return ['school_id' => School::factory(), 'question_bank_id' => QuestionBank::factory(), 'stable_key' => $this->faker->unique()->uuid(), 'type' => 'mcq', 'status' => 'active'];
    }
}

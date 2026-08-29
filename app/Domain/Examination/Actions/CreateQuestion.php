<?php

namespace App\Domain\Examination\Actions;

use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Validation\ValidationException;

class CreateQuestion
{
    public function handle(array $d): Question
    {
        $b = QuestionBank::findOrFail($d['question_bank_id']);
        if ($b->school_id != $d['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }

return Question::create($d);
    }
}

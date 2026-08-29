<?php

namespace App\Domain\Examination\Actions;

use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Validation\ValidationException;

class CreateQuestionBank
{
    public function handle(array $d): QuestionBank
    {
        $s = Subject::findOrFail($d['subject_id']);
        if ($s->school_id != $d['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }

return QuestionBank::create($d);
    }
}

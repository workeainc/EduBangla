<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateQuestion
{
    public function handle(array $d): Question
    {
        $b = QuestionBank::findOrFail($d['question_bank_id']);
        if ($b->school_id != $d['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }

        $question = Question::create($d);
        if ($actor = User::find($d['created_by'] ?? null)) {
            app(RecordAudit::class)->handle($actor, $d['school_id'], 'question.created', $question);
        }

        return $question;
    }
}

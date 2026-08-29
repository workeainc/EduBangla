<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateQuestionBank
{
    public function handle(array $d): QuestionBank
    {
        $s = Subject::findOrFail($d['subject_id']);
        if ($s->school_id != $d['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }

        $bank = QuestionBank::create($d);
        if ($actor = User::find($d['created_by'] ?? null)) {
            app(RecordAudit::class)->handle($actor, $d['school_id'], 'question_bank.created', $bank);
        }

        return $bank;
    }
}

<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateQuestionVersion
{
    public function handle(Question $q, array $d): QuestionVersion
    {
        return DB::transaction(function () use ($q, $d) {
            if ($q->school_id != ($d['school_id'] ?? 0)) {
                throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
            }$d['school_id'] = $q->school_id;
            $d['question_id'] = $q->id;
            $d['version'] = ($q->versions()->max('version') ?? 0) + 1;

            $version = QuestionVersion::create($d);
            if ($actor = User::find($d['created_by'] ?? null)) {
                app(RecordAudit::class)->handle($actor, $q->school_id, 'question.version_created', $version);
            }

            return $version;
        });
    }
}

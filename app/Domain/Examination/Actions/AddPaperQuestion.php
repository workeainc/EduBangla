<?php

namespace App\Domain\Examination\Actions;

use App\Models\ExamPaper;
use App\Models\ExamPaperQuestion;
use App\Models\QuestionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddPaperQuestion
{
    public function handle(array $d): ExamPaperQuestion
    {
        return DB::transaction(function () use ($d) {
            $p = ExamPaper::findOrFail($d['exam_paper_id']);
            $q = QuestionVersion::findOrFail($d['question_version_id']);
            if ($p->school_id != $d['school_id'] || $q->school_id != $p->school_id) {
                throw ValidationException::withMessages(['school_id' => 'Invalid paper question scope.']);
            }

return ExamPaperQuestion::create($d);
        });
    }
}

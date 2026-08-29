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
            if ($p->schedule->exam->isLocked()) {
                throw ValidationException::withMessages(['paper' => 'Locked or published paper cannot be changed.']);
            }
            if (ExamPaperQuestion::where('exam_paper_id', $p->id)->where('question_version_id', $q->id)->exists()) {
                throw ValidationException::withMessages(['question_version_id' => 'Question version ইতোমধ্যে paper-এ আছে।']);
            }

            $d['marks'] = $d['marks'] ?? $q->marks;
            $row = ExamPaperQuestion::create($d);
            $p->update(['total_marks' => $p->questions()->sum('marks')]);

            return $row;
        });
    }
}

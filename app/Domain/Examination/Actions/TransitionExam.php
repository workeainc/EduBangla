<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Examination\ExamStatus;
use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class TransitionExam
{
    public function handle(Exam $exam, string $to): Exam
    {
        $target = ExamStatus::tryFrom($to);
        if (! $target || ExamStatus::next(ExamStatus::from($exam->status)) !== $target) {
            throw ValidationException::withMessages(['status' => 'Invalid examination transition.']);
        }$exam->update(['status' => $target->value, 'locked_at' => $target === ExamStatus::LOCKED ? now() : $exam->locked_at, 'published_at' => $target === ExamStatus::PUBLISHED ? now() : $exam->published_at]);

        if ($actor = auth()->user()) {
            app(RecordAudit::class)->handle($actor, $exam->school_id, 'exam.'.$target->value, $exam, ['status' => $exam->getOriginal('status')], ['status' => $target->value]);
        }

        return $exam->refresh();
    }
}

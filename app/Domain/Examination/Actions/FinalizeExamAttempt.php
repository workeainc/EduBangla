<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeExamAttempt
{
    public function handle(ExamAttempt $attempt, int $schoolId): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $schoolId) {
            if ($attempt->school_id !== $schoolId || $attempt->status !== 'submitted' || $attempt->student?->user_id !== auth()->id()) {
                throw ValidationException::withMessages(['attempt' => 'শুধু submitted attempt finalize করা যায়।']);
            } $attempt->update(['status' => 'finalized', 'finalized_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'exam.attempt_finalized', $attempt, null, ['attempt_id' => $attempt->id]);
            }

            return $attempt->refresh();
        });
    }
}

<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ExamAttempt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitExamAttempt
{
    public function handle(ExamAttempt $attempt, int $schoolId): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $schoolId) {
            $student = Student::where(['school_id' => $schoolId, 'user_id' => auth()->id()])->firstOrFail();
            if ($attempt->school_id !== $schoolId || $attempt->student_id !== $student->id || ! $attempt->isActive()) {
                throw ValidationException::withMessages(['attempt' => 'Attempt access denied or already submitted.']);
            }if (now()->gte($attempt->expires_at)) {
                $attempt->update(['status' => 'finalized', 'finalized_at' => now()]);
                throw ValidationException::withMessages(['attempt' => 'Attempt expired.']);
            }$attempt->update(['status' => 'submitted', 'submitted_at' => now(), 'finalized_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'exam.attempt_submitted', $attempt, null, ['submitted_at' => $attempt->submitted_at]);
            }

return $attempt->refresh();
        });
    }
}

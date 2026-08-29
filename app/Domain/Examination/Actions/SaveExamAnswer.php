<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveExamAnswer
{
    public function handle(ExamAttempt $attempt, int $questionId, mixed $answer, int $schoolId): ExamAnswer
    {
        return DB::transaction(function () use ($attempt, $questionId, $answer, $schoolId) {
            $student = Student::where(['school_id' => $schoolId, 'user_id' => auth()->id()])->firstOrFail();
            $this->active($attempt, $student->id, $schoolId);
            $q = ExamAttemptQuestion::where(['school_id' => $schoolId, 'exam_attempt_id' => $attempt->id])->findOrFail($questionId);
            $payload = is_array($answer) ? $answer : ['value' => (string) $answer];
            if (in_array($q->question_type, ['mcq', 'true_false'], true)) {
                $keys = collect($q->options_snapshot ?? [])->pluck('option_key');
                if (! $keys->contains($payload['value'] ?? null)) {
                    throw ValidationException::withMessages(['answer' => 'Invalid option.']);
                }
            } elseif (in_array($q->question_type, ['short_answer', 'descriptive'], true)) {
                if (! is_string($payload['value'] ?? null) || trim($payload['value']) === '' || mb_strlen($payload['value']) > 10000) {
                    throw ValidationException::withMessages(['answer' => 'উত্তরটি বৈধ text হতে হবে।']);
                }
            } else {
                throw ValidationException::withMessages(['answer' => 'অজানা প্রশ্নের ধরন।']);
            }
            $row = ExamAnswer::updateOrCreate(['exam_attempt_question_id' => $q->id], ['school_id' => $schoolId, 'exam_attempt_id' => $attempt->id, 'answer_payload' => $payload, 'answered_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'exam.answer_saved', $row, null, ['attempt_id' => $attempt->id, 'question_id' => $q->id]);
            }

            return $row;
        });
    }

    public function active(ExamAttempt $a, int $studentId, int $schoolId): void
    {
        if ($a->school_id !== $schoolId || $a->student_id !== $studentId || ! $a->isActive()) {
            throw ValidationException::withMessages(['attempt' => 'Attempt access denied.']);
        }if (now()->gte($a->expires_at)) {
            throw ValidationException::withMessages(['attempt' => 'Attempt expired.']);
        }
    }
}

<?php

namespace App\Domain\Examination\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ExamMark;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectExamMark
{
    public function handle(ExamMark $mark, int $newMarks, int $actorId): ExamMark
    {
        if (! auth()->user()?->schoolMemberships()->where(['school_id' => $mark->school_id, 'role' => 'school-admin', 'status' => 'active'])->exists()) {
            throw new AuthorizationException;
        } if ($newMarks < 0 || $newMarks > $mark->maximum_marks) {
            throw ValidationException::withMessages(['marks' => 'Marks exceed maximum.']);
        }

return DB::transaction(function () use ($mark, $newMarks) {
            $before = ['marks' => $mark->marks];
            $mark->update(['marks' => $newMarks, 'entered_by' => auth()->id(), 'entered_at' => now()]);
            app(RecordAudit::class)->handle(auth()->user(), $mark->school_id, 'exam.mark_corrected', $mark, $before, ['marks' => $newMarks]);

            return $mark->refresh();
        });
    }
}

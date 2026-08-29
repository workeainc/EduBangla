<?php

namespace App\Domain\Examination\Actions;

use App\Models\ExamPaperQuestion;
use Illuminate\Validation\ValidationException;

class ReorderPaperQuestion
{
    public function handle(int $id, int $ordinal, int $schoolId): ExamPaperQuestion
    {
        $r = ExamPaperQuestion::with('paper.schedule.exam')->where('school_id', $schoolId)->findOrFail($id);
        if ($r->paper->schedule->exam->isLocked()) {
            throw ValidationException::withMessages(['paper' => 'Locked paper.']);
        }$r->update(['ordinal' => $ordinal]);

        return $r->refresh();
    }
}

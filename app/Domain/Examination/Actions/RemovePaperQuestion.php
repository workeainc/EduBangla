<?php

namespace App\Domain\Examination\Actions;

use App\Models\ExamPaperQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemovePaperQuestion
{
    public function handle(int $id, int $schoolId): void
    {
        DB::transaction(function () use ($id, $schoolId) {
            $r = ExamPaperQuestion::with('paper.schedule.exam')->where('school_id', $schoolId)->findOrFail($id);
            if ($r->paper->schedule->exam->isLocked()) {
                throw ValidationException::withMessages(['paper' => 'Locked paper.']);
            }$p = $r->paper;
            $r->delete();
            $p->update(['total_marks' => $p->questions()->sum('marks')]);
        });
    }
}

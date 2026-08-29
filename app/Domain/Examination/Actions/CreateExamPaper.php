<?php

namespace App\Domain\Examination\Actions;

use App\Models\ExamPaper;
use App\Models\ExamSchedule;
use Illuminate\Validation\ValidationException;

class CreateExamPaper
{
    public function handle(array $d): ExamPaper
    {
        $s = ExamSchedule::findOrFail($d['exam_schedule_id']);
        if ($s->school_id != $d['school_id']) {
            throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
        }

        return ExamPaper::create($d);
    }
}

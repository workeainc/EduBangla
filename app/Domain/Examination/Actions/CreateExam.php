<?php

namespace App\Domain\Examination\Actions;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateExam
{
    public function handle(array $d): Exam
    {
        return DB::transaction(function () use ($d) {
            $t = ExamType::findOrFail($d['exam_type_id']);
            $y = AcademicYear::findOrFail($d['academic_year_id']);
            if ($t->school_id != $d['school_id'] || $y->school_id != $d['school_id']) {
                throw ValidationException::withMessages(['school_id' => 'Invalid school scope.']);
            }

            return Exam::create($d + ['status' => 'draft']);
        });
    }
}

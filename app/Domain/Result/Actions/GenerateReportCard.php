<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ReportCard;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateReportCard
{
    public function handle(Result $result, int $schoolId): ReportCard
    {
        return DB::transaction(function () use ($result, $schoolId) {
            if ($result->school_id !== $schoolId || ! in_array($result->status, ['locked', 'published'], true) || ! $result->gpa) {
                throw ValidationException::withMessages(['result' => 'Only graded locked or published results can generate a report card.']);
            } if (ReportCard::where(['school_id' => $schoolId, 'result_id' => $result->id])->exists()) {
                throw ValidationException::withMessages(['report' => 'Report card already exists.']);
            } $card = ReportCard::create(['school_id' => $schoolId, 'result_id' => $result->id, 'student_id' => $result->student_id, 'enrollment_id' => $result->enrollment_id, 'exam_id' => $result->exam_id, 'status' => 'generated', 'gpa' => $result->gpa, 'overall_status' => $result->overall_status, 'snapshot' => $result->load('items')->toArray()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'report_card.generated', $card);
            }

            return $card;
        });
    }
}

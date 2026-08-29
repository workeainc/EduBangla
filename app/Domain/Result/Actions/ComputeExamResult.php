<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComputeExamResult
{
    public function handle(Exam $exam, int $schoolId): int
    {
        return DB::transaction(function () use ($exam, $schoolId) {
            if ($exam->school_id !== $schoolId) {
                throw ValidationException::withMessages(['exam' => 'Invalid exam scope.']);
            }
            if (Result::where('school_id', $schoolId)->where('exam_id', $exam->id)->whereIn('status', ['locked', 'published'])->exists()) {
                throw ValidationException::withMessages(['exam' => 'Locked or published results cannot be recomputed.']);
            }
            $marks = $exam->schedules()->with(['subject', 'marks'])->where('school_id', $schoolId)->get()->flatMap(fn ($s) => $s->marks->map(fn ($m) => [$m, $s]));
            $count = 0;
            foreach ($marks->groupBy(fn ($x) => $x[0]->student_id) as $studentId => $rows) {
                $first = $rows->first()[0];
                $enrollment = Enrollment::where(['school_id' => $schoolId, 'student_id' => $studentId, 'academic_year_id' => $exam->academic_year_id, 'status' => 'active'])->first();
                if (! $enrollment) {
                    continue;
                }$result = Result::updateOrCreate(['exam_id' => $exam->id, 'student_id' => $studentId, 'enrollment_id' => $enrollment->id], ['school_id' => $schoolId, 'status' => 'computed', 'total_obtained' => $rows->sum(fn ($x) => $x[0]->marks), 'total_marks' => $rows->sum(fn ($x) => $x[0]->maximum_marks), 'percentage' => round($rows->sum(fn ($x) => $x[0]->marks) / max(1, $rows->sum(fn ($x) => $x[0]->maximum_marks)) * 100, 2), 'computed_at' => now()]);
                $result->items()->delete();
                foreach ($rows as [$mark,$schedule]) {
                    $result->items()->create(['school_id' => $schoolId, 'subject_id' => $schedule->subject_id, 'exam_schedule_id' => $schedule->id, 'obtained_marks' => $mark->marks, 'maximum_marks' => $mark->maximum_marks, 'percentage' => round($mark->marks / max(1, $mark->maximum_marks) * 100, 2), 'source' => 'manual']);
                }$count++;
                if (auth()->user()) {
                    app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'result.computed', $result);
                }
            }$exam->unsetRelation('schedules');

            return $count;
        });
    }
}

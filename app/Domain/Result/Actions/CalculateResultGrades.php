<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\GradeRule;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateResultGrades
{
    public function handle(Result $result, int $schoolId): Result
    {
        return DB::transaction(function () use ($result, $schoolId) {
            if ($result->school_id !== $schoolId || in_array($result->status, ['locked', 'published'], true)) {
                throw ValidationException::withMessages(['result' => 'Locked or published results cannot be recalculated.']);
            }
            $rules = GradeRule::where('school_id', $schoolId)->where('active', true)->orderBy('sort_order')->get();
            if ($rules->isEmpty()) {
                throw ValidationException::withMessages(['grade' => 'No active grade rules configured.']);
            }
            $items = $result->items()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['result' => 'Result has no gradeable items.']);
            }
            $points = 0;
            $failed = false;
            foreach ($items as $item) {
                $p = (float) $item->percentage;
                $rule = $rules->first(fn ($r) => $p >= (float) $r->minimum_percentage && $p <= (float) $r->maximum_percentage);
                if (! $rule) {
                    throw ValidationException::withMessages(['grade' => 'No grade rule covers '.$p.'%.']);
                } $item->update(['grade_rule_id' => $rule->id, 'letter_grade' => $rule->letter_grade, 'grade_point' => $rule->grade_point, 'is_pass' => $rule->is_pass]);
                $points += (float) $rule->grade_point;
                if (! $rule->is_pass) {
                    $failed = true;
                }
            }
            $gpa = round($points / $items->count(), 2);
            $result->update(['gpa' => $gpa, 'total_grade_points' => $points, 'graded_subject_count' => $items->count(), 'overall_status' => $failed ? 'fail' : 'pass']);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'result.grades_calculated', $result);
            }

return $result->refresh();
        });
    }
}

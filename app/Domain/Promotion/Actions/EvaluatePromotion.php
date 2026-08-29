<?php

namespace App\Domain\Promotion\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\PromotionRule;
use App\Models\ReportCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluatePromotion
{
    public function handle(Promotion $p, int $schoolId): Promotion
    {
        return DB::transaction(function () use ($p, $schoolId) {
            if ($p->school_id !== $schoolId || $p->status !== 'draft') {
                throw ValidationException::withMessages(['promotion' => 'Only draft promotions can be evaluated.']);
            } $en = Enrollment::where(['id' => $p->source_enrollment_id, 'school_id' => $schoolId, 'student_id' => $p->student_id, 'academic_year_id' => $p->academic_year_id, 'class_id' => $p->source_class_id, 'section_id' => $p->source_section_id, 'status' => 'active'])->first();
            if (! $en) {
                throw ValidationException::withMessages(['promotion' => 'Invalid source enrollment scope.']);
            } $card = ReportCard::where(['school_id' => $schoolId, 'student_id' => $p->student_id, 'status' => 'published'])->where('enrollment_id', $en->id)->latest()->first();
            if (! $card) {
                throw ValidationException::withMessages(['promotion' => 'Published report card is required.']);
            } $rule = PromotionRule::where(['school_id' => $schoolId, 'source_class_id' => $p->source_class_id, 'target_class_id' => $p->target_class_id, 'active' => true])->first();
            if (! $rule) {
                throw ValidationException::withMessages(['promotion' => 'No active promotion rule.']);
            } $eligible = $card->overall_status === 'pass' && (! $rule->minimum_gpa || ((float) $card->gpa >= (float) $rule->minimum_gpa));
            $basis = ['report_card_id' => $card->id, 'gpa' => $card->gpa, 'overall_status' => $card->overall_status, 'rule_id' => $rule->id];
            $p->update(['status' => 'eligible', 'decision' => $eligible ? 'eligible' : 'ineligible', 'eligibility_basis' => $basis]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'promotion.evaluated', $p);
            }

return $p->refresh();
        });
    }
}

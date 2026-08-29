<?php

namespace App\Domain\Promotion\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Promotion;
use App\Models\ReportCard;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPromotion
{
    public function handle(Promotion $p, int $schoolId, ?\Closure $afterEnrollment = null): Promotion
    {
        return DB::transaction(function () use ($p, $schoolId) {
            if ($p->school_id !== $schoolId || $p->status !== 'approved') {
                throw ValidationException::withMessages(['promotion' => 'Only approved promotions can be applied.']);
            }
            if (! ReportCard::where(['school_id' => $schoolId, 'student_id' => $p->student_id, 'enrollment_id' => $p->source_enrollment_id, 'status' => 'published'])->exists()) {
                throw ValidationException::withMessages(['promotion' => 'Published report card is required.']);
            }
            if (! AcademicYear::where(['id' => $p->target_academic_year_id, 'school_id' => $schoolId])->exists() || ! AcademicClass::where(['id' => $p->target_class_id, 'school_id' => $schoolId])->exists() || ($p->target_section_id && ! Section::where(['id' => $p->target_section_id, 'school_id' => $schoolId, 'class_id' => $p->target_class_id])->exists())) {
                throw ValidationException::withMessages(['promotion' => 'Invalid target academic scope.']);
            }
            if (Enrollment::where(['school_id' => $schoolId, 'student_id' => $p->student_id, 'academic_year_id' => $p->target_academic_year_id, 'status' => 'active'])->exists()) {
                throw ValidationException::withMessages(['promotion' => 'Target enrollment already exists.']);
            }
            $target = Enrollment::create(['school_id' => $schoolId, 'student_id' => $p->student_id, 'academic_year_id' => $p->target_academic_year_id, 'class_id' => $p->target_class_id, 'section_id' => $p->target_section_id, 'status' => 'active', 'enrolled_at' => now()->toDateString()]);
            if ($afterEnrollment) {
                $afterEnrollment($target);
            }
            $p->update(['status' => 'applied', 'target_enrollment_id' => $target->id]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'promotion.applied', $p);
            }

            return $p->refresh();
        });
    }
}

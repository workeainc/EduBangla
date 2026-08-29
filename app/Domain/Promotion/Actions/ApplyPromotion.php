<?php

namespace App\Domain\Promotion\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Enrollment;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPromotion
{
    public function handle(Promotion $p, int $schoolId): Promotion
    {
        return DB::transaction(function () use ($p, $schoolId) {
            if ($p->school_id !== $schoolId || $p->status !== 'approved') {
                throw ValidationException::withMessages(['promotion' => 'Only approved promotions can be applied.']);
            } if (Enrollment::where(['school_id' => $schoolId, 'student_id' => $p->student_id, 'academic_year_id' => $p->target_academic_year_id, 'status' => 'active'])->exists()) {
                throw ValidationException::withMessages(['promotion' => 'Target enrollment already exists.']);
            } $target = Enrollment::create(['school_id' => $schoolId, 'student_id' => $p->student_id, 'academic_year_id' => $p->target_academic_year_id, 'class_id' => $p->target_class_id, 'section_id' => $p->target_section_id, 'status' => 'active', 'enrolled_at' => now()->toDateString()]);
            $p->update(['status' => 'applied', 'target_enrollment_id' => $target->id]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'promotion.applied', $p);
            }

return $p->refresh();
        });
    }
}

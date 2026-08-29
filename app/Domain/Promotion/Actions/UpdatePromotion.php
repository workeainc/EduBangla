<?php

namespace App\Domain\Promotion\Actions;

use App\Models\Enrollment;
use App\Models\Promotion;
use Illuminate\Validation\ValidationException;

class UpdatePromotion
{
    public function handle(Promotion $p, array $data, int $schoolId): Promotion
    {
        if ($p->school_id !== $schoolId || ! in_array($p->status, ['draft', 'eligible'], true)) {
            throw ValidationException::withMessages(['promotion' => 'Promotion is not editable in this state.']);
        } if (isset($data['source_enrollment_id']) && ! Enrollment::where(['id' => $data['source_enrollment_id'], 'school_id' => $schoolId, 'student_id' => $data['student_id'] ?? $p->student_id])->exists()) {
            throw ValidationException::withMessages(['source_enrollment_id' => 'Invalid tenant enrollment.']);
        } $p->update($data);

        return $p->refresh();
    }
}

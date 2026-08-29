<?php

namespace App\Domain\Promotion\Actions;

use App\Models\Promotion;
use Illuminate\Validation\ValidationException;

class UpdatePromotion
{
    public function handle(Promotion $p, array $data, int $schoolId): Promotion
    {
        if ($p->school_id !== $schoolId || ! in_array($p->status, ['draft', 'eligible'], true)) {
            throw ValidationException::withMessages(['promotion' => 'Promotion is not editable in this state.']);
        }
        $scope = array_merge($p->getAttributes(), $data);
        app(CreatePromotion::class)->validateScope($scope, $schoolId);
        if ($p->status === 'eligible' && array_intersect(array_keys($data), ['student_id', 'source_enrollment_id', 'academic_year_id', 'source_class_id', 'source_section_id', 'target_academic_year_id', 'target_class_id', 'target_section_id'])) {
            $data += ['status' => 'draft', 'decision' => null, 'eligibility_basis' => null];
        }
        $p->update($data);

        return $p->refresh();
    }
}

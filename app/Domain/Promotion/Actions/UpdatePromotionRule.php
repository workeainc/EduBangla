<?php

namespace App\Domain\Promotion\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\PromotionRule;
use Illuminate\Validation\ValidationException;

class UpdatePromotionRule
{
    public function handle(PromotionRule $rule, array $data, int $schoolId): PromotionRule
    {
        if ($rule->school_id !== $schoolId) {
            throw ValidationException::withMessages(['rule' => 'Invalid tenant scope.']);
        }
        foreach (['academic_year_id' => AcademicYear::class, 'source_class_id' => AcademicClass::class, 'target_class_id' => AcademicClass::class] as $key => $model) {
            if (array_key_exists($key, $data) && ! $model::where(['id' => $data[$key], 'school_id' => $schoolId])->exists()) {
                throw ValidationException::withMessages([$key => 'Invalid tenant scope.']);
            }
        }
        $source = $data['source_class_id'] ?? $rule->source_class_id;
        $target = $data['target_class_id'] ?? $rule->target_class_id;
        if ((int) $source === (int) $target) {
            throw ValidationException::withMessages(['target_class_id' => 'Target class must differ.']);
        }
        $rule->update($data);

        return $rule->refresh();
    }
}

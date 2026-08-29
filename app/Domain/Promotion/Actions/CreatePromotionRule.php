<?php

namespace App\Domain\Promotion\Actions;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\PromotionRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePromotionRule
{
    public function handle(array $data, int $schoolId): PromotionRule
    {
        foreach (['academic_year_id' => AcademicYear::class, 'source_class_id' => AcademicClass::class, 'target_class_id' => AcademicClass::class] as $key => $model) {
            if (! $model::where(['id' => $data[$key] ?? 0, 'school_id' => $schoolId])->exists()) {
                throw ValidationException::withMessages([$key => 'Invalid tenant scope.']);
            }
        } if (($data['source_class_id'] ?? 0) == ($data['target_class_id'] ?? 0)) {
            throw ValidationException::withMessages(['target_class_id' => 'Target class must differ.']);
        }

        return DB::transaction(fn () => PromotionRule::create($data + ['school_id' => $schoolId, 'active' => true]));
    }
}

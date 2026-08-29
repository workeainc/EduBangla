<?php

namespace App\Domain\Promotion\Actions;

use App\Models\PromotionRule;
use Illuminate\Validation\ValidationException;

class DeactivatePromotionRule
{
    public function handle(PromotionRule $rule, int $schoolId): PromotionRule
    {
        if ($rule->school_id !== $schoolId) {
            throw ValidationException::withMessages(['rule' => 'Invalid tenant scope.']);
        } $rule->update(['active' => false]);

        return $rule->refresh();
    }
}

<?php

namespace App\Domain\Promotion\Actions;

use App\Models\PromotionRule;
use Illuminate\Validation\ValidationException;

class ActivatePromotionRule
{
    public function handle(PromotionRule $rule, int $schoolId): PromotionRule
    {
        if ($rule->school_id !== $schoolId) {
            throw ValidationException::withMessages(['rule' => 'Invalid tenant scope.']);
        } $rule->update(['active' => true]);

        return $rule->refresh();
    }
}

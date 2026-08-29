<?php

namespace App\Domain\Promotion\Actions;

use App\Models\PromotionRule;
use Illuminate\Validation\ValidationException;

class UpdatePromotionRule
{
    public function handle(PromotionRule $rule, array $data, int $schoolId): PromotionRule
    {
        if ($rule->school_id !== $schoolId) {
            throw ValidationException::withMessages(['rule' => 'Invalid tenant scope.']);
        } $rule->update($data);

        return $rule->refresh();
    }
}

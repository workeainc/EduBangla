<?php

namespace App\Policies;

use App\Models\Promotion;

class PromotionPolicy extends SchoolOwnedPolicy
{
    public function view($user, Promotion $p): bool
    {
        return parent::view($user, $p) || ($p->student?->user_id === $user->id && $p->status === 'applied');
    }
}

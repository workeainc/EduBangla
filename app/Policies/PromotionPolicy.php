<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy extends SchoolOwnedPolicy
{
    public function view(User $user, Promotion $p): bool
    {
        return parent::view($user, $p) || ($p->student?->user_id === $user->id && $p->status === 'applied');
    }
}

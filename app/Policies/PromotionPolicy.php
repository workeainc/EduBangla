<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PromotionPolicy extends SchoolOwnedPolicy
{
    public function view(User $user, Model $p): bool
    {
        return parent::view($user, $p) || ($p->student?->user_id === $user->id && $p->status === 'applied');
    }
}

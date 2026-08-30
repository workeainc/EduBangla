<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy extends SchoolOwnedPolicy
{
    public function view(User $user, Result $result): bool
    {
        if (parent::view($user, $result)) {
            return true;
        }

        return $result->student()->where('user_id', $user->id)->exists() && $result->status === 'published' && $user->schoolMemberships()->where(['school_id' => $result->school_id, 'role' => 'student', 'status' => 'active'])->exists();
    }
}

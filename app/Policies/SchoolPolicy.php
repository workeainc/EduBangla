<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function view(User $user, School $school): bool
    {
        return $school->hasActiveMember($user);
    }

    public function update(User $user, School $school): bool
    {
        return $school->memberships()->where('user_id', $user->id)->where('status', 'active')->where('role', 'school-admin')->exists();
    }
}

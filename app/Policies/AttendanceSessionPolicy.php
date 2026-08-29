<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;

class AttendanceSessionPolicy extends SchoolOwnedPolicy
{
    public function update(User $user, AttendanceSession $session): bool
    {
        if ($session->isFinalized()) {
            return $user->hasRole('School Admin');
        }

        return $user->hasRole('School Admin') || ($user->hasRole('Teacher') && $session->teacher?->user_id === $user->id);
    }

    public function finalize(User $user, AttendanceSession $session): bool
    {
        return ! $session->isFinalized() && ($user->hasRole('School Admin') || ($user->hasRole('Teacher') && $session->teacher?->user_id === $user->id));
    }
}

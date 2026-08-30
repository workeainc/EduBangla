<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\SchoolUser;
use App\Models\User;

class AttendanceSessionPolicy extends SchoolOwnedPolicy
{
    public function update(User $user, AttendanceSession $session): bool
    {
        if ($session->isFinalized()) {
            return $this->hasRole($user, $session->school_id, 'school-admin');
        }

        return $this->hasRole($user, $session->school_id, 'school-admin') || ($this->hasRole($user, $session->school_id, 'teacher') && $session->teacher?->user_id === $user->id);
    }

    public function finalize(User $user, AttendanceSession $session): bool
    {
        return ! $session->isFinalized() && ($this->hasRole($user, $session->school_id, 'school-admin') || ($this->hasRole($user, $session->school_id, 'teacher') && $session->teacher?->user_id === $user->id));
    }

    private function hasRole(User $user, int $schoolId, string $role): bool
    {
        return SchoolUser::where(['school_id' => $schoolId, 'user_id' => $user->id, 'role' => $role, 'status' => 'active'])->exists();
    }
}

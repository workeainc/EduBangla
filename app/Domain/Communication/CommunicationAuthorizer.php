<?php

namespace App\Domain\Communication;

use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CommunicationAuthorizer
{
    public const RECIPIENT_ROLES = ['school-admin', 'teacher', 'student', 'staff'];

    public static function admin(User $actor, int $schoolId): void
    {
        if (! SchoolUser::where(['school_id' => $schoolId, 'user_id' => $actor->id, 'role' => 'school-admin', 'status' => 'active'])->exists()) {
            throw new AuthorizationException('School admin access is required.');
        }
    }

    public static function recipient(User $actor, int $schoolId, string $role): void
    {
        if (! in_array($role, self::RECIPIENT_ROLES, true) || ! SchoolUser::where(['school_id' => $schoolId, 'user_id' => $actor->id, 'role' => $role, 'status' => 'active'])->exists()) {
            throw new AuthorizationException('Active recipient membership is required.');
        }
    }
}

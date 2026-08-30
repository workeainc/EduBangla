<?php

namespace App\Domain\Finance;

use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FinanceAuthorizer
{
    public static function admin(User $actor, int $schoolId): void
    {
        if (! SchoolUser::where(['school_id' => $schoolId, 'user_id' => $actor->id, 'role' => 'school-admin', 'status' => 'active'])->exists()) {
            throw new AuthorizationException('School admin access is required.');
        }
    }

    public static function student(User $actor, int $schoolId): Student
    {
        if (! SchoolUser::where(['school_id' => $schoolId, 'user_id' => $actor->id, 'role' => 'student', 'status' => 'active'])->exists()) {
            throw new AuthorizationException('Student access is required.');
        }

        return Student::where(['school_id' => $schoolId, 'user_id' => $actor->id, 'status' => 'active'])->firstOrFail();
    }
}

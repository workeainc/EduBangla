<?php

namespace App\Policies;

use App\Models\ExamMark;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;

class ExamMarkPolicy extends SchoolOwnedPolicy
{
    public function create(User $u, int $schoolId): bool
    {
        return SchoolUser::where(['user_id' => $u->id, 'school_id' => $schoolId, 'status' => 'active'])->whereIn('role', ['school-admin', 'teacher'])->exists();
    }

    public function update(User $u, ExamMark $m): bool
    {
        return $this->create($u, $m->school_id) && ($u->hasRole('School Admin') || Teacher::where('user_id', $u->id)->whereKey($m->teacher_id)->exists());
    }
}

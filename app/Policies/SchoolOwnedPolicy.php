<?php

namespace App\Policies;

use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class SchoolOwnedPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->isSchoolAdmin($user, $model->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $this->isSchoolAdmin($user, $schoolId);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->isSchoolAdmin($user, $model->school_id);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->isSchoolAdmin($user, $model->school_id);
    }

    protected function isSchoolAdmin(User $user, int $schoolId): bool
    {
        return SchoolUser::query()->where('user_id', $user->id)->where('school_id', $schoolId)->where('status', 'active')->where('role', 'school-admin')->exists();
    }
}

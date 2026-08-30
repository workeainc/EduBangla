<?php

namespace App\Policies;

use App\Models\SchoolUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class FinanceAdminPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->admin($user, $model->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $this->admin($user, $schoolId);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->admin($user, $model->school_id);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->admin($user, $model->school_id);
    }

    protected function admin(User $user, int $schoolId): bool
    {
        return SchoolUser::where(['school_id' => $schoolId, 'user_id' => $user->id, 'role' => 'school-admin', 'status' => 'active'])->exists();
    }
}

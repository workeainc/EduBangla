<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        return $attempt->student()->where('user_id', $user->id)->exists() && $attempt->school_id && $user->schoolMemberships()->where(['school_id' => $attempt->school_id, 'status' => 'active'])->exists();
    }

    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $this->view($user, $attempt) && $attempt->isActive();
    }
}

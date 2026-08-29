<?php

namespace App\Policies;

use App\Models\ExamAnswer;
use App\Models\User;

class ExamAnswerPolicy
{
    public function view(User $user, ExamAnswer $answer): bool
    {
        return $answer->attempt && app(ExamAttemptPolicy::class)->view($user, $answer->attempt);
    }

    public function update(User $user, ExamAnswer $answer): bool
    {
        return $answer->attempt && app(ExamAttemptPolicy::class)->update($user, $answer->attempt);
    }
}

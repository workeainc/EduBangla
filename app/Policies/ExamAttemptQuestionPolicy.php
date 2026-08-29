<?php

namespace App\Policies;

use App\Models\ExamAttemptQuestion;
use App\Models\User;

class ExamAttemptQuestionPolicy
{
    public function view(User $user, ExamAttemptQuestion $question): bool
    {
        return $question->attempt && app(ExamAttemptPolicy::class)->view($user, $question->attempt);
    }

    public function update(User $user, ExamAttemptQuestion $question): bool
    {
        return $question->attempt && app(ExamAttemptPolicy::class)->update($user, $question->attempt);
    }
}

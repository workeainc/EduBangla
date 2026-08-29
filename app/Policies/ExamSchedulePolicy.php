<?php

namespace App\Policies;

use App\Models\ExamSchedule;
use App\Models\Teacher;
use App\Models\User;

class ExamSchedulePolicy extends SchoolOwnedPolicy
{
    public function view(User $u, ExamSchedule $m): bool
    {
        return parent::view($u, $m) || ($u->hasRole('Teacher') && Teacher::where('school_id', $m->school_id)->where('user_id', $u->id)->whereKey($m->teacher_id)->exists());
    }
}

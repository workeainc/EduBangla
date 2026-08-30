<?php

namespace App\Policies;

use App\Models\ExamSchedule;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\User;

class ExamSchedulePolicy extends SchoolOwnedPolicy
{
    public function view(User $u, ExamSchedule $m): bool
    {
        return parent::view($u, $m) || (SchoolUser::where(['school_id' => $m->school_id, 'user_id' => $u->id, 'role' => 'teacher', 'status' => 'active'])->exists() && Teacher::where('school_id', $m->school_id)->where('user_id', $u->id)->whereKey($m->teacher_id)->exists());
    }
}

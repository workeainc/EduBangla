<?php

namespace App\Policies;

use App\Models\SchoolUser;
use App\Models\Timetable;
use App\Models\User;

class TimetablePolicy
{
    public function manage(User $user, Timetable $timetable): bool
    {
        return SchoolUser::where(['school_id' => $timetable->school_id, 'user_id' => $user->id, 'role' => 'school-admin', 'status' => 'active'])->exists();
    }

    public function view(User $user, Timetable $timetable): bool
    {
        return $this->manage($user, $timetable);
    }
}

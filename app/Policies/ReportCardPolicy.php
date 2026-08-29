<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\SchoolUser;

class ReportCardPolicy extends SchoolOwnedPolicy
{
    public function view($user, ReportCard $card): bool
    {
        if ($this->isSchoolAdmin($user, $card->school_id)) {
            return true;
        }

        return $card->status === 'published' && $card->student?->user_id === $user->id && SchoolUser::where(['user_id' => $user->id, 'school_id' => $card->school_id, 'status' => 'active', 'role' => 'student'])->exists();
    }
}

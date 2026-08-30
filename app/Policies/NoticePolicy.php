<?php

namespace App\Policies;

use App\Domain\Communication\CommunicationAuthorizer;
use App\Models\Notice;
use App\Models\User;

class NoticePolicy
{
    public function manage(User $user, Notice $notice): bool
    {
        try {
            CommunicationAuthorizer::admin($user, $notice->school_id);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function create(User $user, int $schoolId): bool
    {
        try {
            CommunicationAuthorizer::admin($user, $schoolId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

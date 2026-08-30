<?php

namespace App\Policies;

use App\Domain\Communication\CommunicationAuthorizer;
use App\Models\NoticeDelivery;
use App\Models\User;

class NoticeDeliveryPolicy
{
    public function view(User $user, NoticeDelivery $delivery): bool
    {
        try {
            CommunicationAuthorizer::admin($user, $delivery->school_id);

            return true;
        } catch (\Throwable) {
            try {
                CommunicationAuthorizer::recipient($user, $delivery->school_id, $delivery->recipient_role);

                return $delivery->user_id === $user->id;
            } catch (\Throwable) {
                return false;
            }
        }
    }

    public function markRead(User $user, NoticeDelivery $delivery): bool
    {
        return $this->view($user, $delivery) && $delivery->user_id === $user->id;
    }
}

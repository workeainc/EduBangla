<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends FinanceAdminPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return parent::view($user, $payment) || ($payment->student?->user_id === $user->id && $user->schoolMemberships()->where(['school_id' => $payment->school_id, 'role' => 'student', 'status' => 'active'])->exists());
    }
}

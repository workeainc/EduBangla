<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends FinanceAdminPolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return parent::view($user, $invoice) || ($invoice->status !== 'void' && $invoice->student?->user_id === $user->id && $user->schoolMemberships()->where(['school_id' => $invoice->school_id, 'role' => 'student', 'status' => 'active'])->exists());
    }
}

<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReversePayment
{
    public function handle(User $actor, int $schoolId, int $paymentId, ?\Closure $afterMutation = null): Payment
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $paymentId, $afterMutation) {
            $payment = Payment::with('allocations')->where(['school_id' => $schoolId, 'id' => $paymentId])->lockForUpdate()->firstOrFail();
            if (! in_array($payment->status, ['recorded', 'allocated'], true) || $payment->reversal_of_payment_id) {
                throw ValidationException::withMessages(['payment' => 'Only an unreversed payment can be reversed.']);
            }
            $before = $payment->only(['status']);
            $payment->update(['status' => 'reversed', 'reversed_at' => now()]);
            $reversal = Payment::create(['school_id' => $schoolId, 'student_id' => $payment->student_id, 'enrollment_id' => $payment->enrollment_id, 'receipt_number' => 'REV-'.$schoolId.'-'.strtoupper(bin2hex(random_bytes(5))), 'currency' => $payment->currency, 'amount' => $payment->amount, 'received_at' => now()->toDateString(), 'method' => 'reversal', 'reference' => $payment->receipt_number, 'recorded_by' => $actor->id, 'status' => 'reversal', 'reversal_of_payment_id' => $payment->id, 'reversed_at' => now()]);
            if ($afterMutation) {
                $afterMutation($payment, $reversal);
            }
            foreach ($payment->allocations as $allocation) {
                app(FinanceBalance::class)->refresh($allocation->invoice, $schoolId);
            }
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.payment_reversed', $payment, $before, ['status' => 'reversed', 'reversal_id' => $reversal->id]);

            return $reversal->refresh();
        });
    }
}

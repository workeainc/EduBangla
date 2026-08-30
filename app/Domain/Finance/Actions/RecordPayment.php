<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    public function handle(User $actor, int $schoolId, array $data, ?\Closure $afterFirst = null): Payment
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $data, $afterFirst) {
            $enrollment = Enrollment::where(['school_id' => $schoolId, 'id' => $data['enrollment_id'], 'status' => 'active'])->firstOrFail();
            if ($enrollment->student_id !== (int) $data['student_id']) {
                throw ValidationException::withMessages(['student_id' => 'Payment student does not match enrollment.']);
            }
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be positive.']);
            }
            $allocations = $data['allocations'] ?? [];
            if (count($allocations) === 0) {
                throw ValidationException::withMessages(['allocations' => 'At least one allocation is required.']);
            }
            if (count(array_unique(array_column($allocations, 'invoice_id'))) !== count($allocations)) {
                throw ValidationException::withMessages(['allocations' => 'An invoice can be allocated only once per payment.']);
            }
            $payment = Payment::create(['school_id' => $schoolId, 'student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id, 'receipt_number' => 'REC-'.$schoolId.'-'.strtoupper(bin2hex(random_bytes(5))), 'currency' => 'BDT', 'amount' => $amount, 'received_at' => $data['received_at'] ?? now()->toDateString(), 'method' => $data['method'] ?? 'cash', 'reference' => $data['reference'] ?? null, 'recorded_by' => $actor->id, 'status' => 'recorded']);
            $allocated = 0.0;
            foreach ($allocations as $allocation) {
                $invoice = Invoice::where(['school_id' => $schoolId, 'id' => $allocation['invoice_id'], 'student_id' => $enrollment->student_id, 'enrollment_id' => $enrollment->id])->lockForUpdate()->firstOrFail();
                if ($invoice->status === 'void') {
                    throw ValidationException::withMessages(['invoice' => 'Void invoices cannot receive payments.']);
                }
                $due = (float) app(FinanceBalance::class)->calculate($invoice, $schoolId)['outstanding_total'];
                $line = round((float) $allocation['amount'], 2);
                if ($line <= 0 || $line > $due) {
                    throw ValidationException::withMessages(['allocations' => 'Allocation exceeds invoice due.']);
                }
                $payment->allocations()->create(['school_id' => $schoolId, 'invoice_id' => $invoice->id, 'amount' => $line]);
                $allocated += $line;
                app(FinanceBalance::class)->refresh($invoice, $schoolId);
                if ($afterFirst) {
                    $afterFirst($payment);
                }
            }
            if (round($allocated, 2) !== $amount) {
                throw ValidationException::withMessages(['allocations' => 'Allocations must equal the payment amount.']);
            }
            $payment->update(['status' => 'allocated']);
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.payment_recorded', $payment, null, ['receipt_number' => $payment->receipt_number, 'amount' => (string) $payment->amount, 'allocation_total' => number_format($allocated, 2, '.', '')]);

            return $payment->refresh()->load('allocations.invoice');
        });
    }
}

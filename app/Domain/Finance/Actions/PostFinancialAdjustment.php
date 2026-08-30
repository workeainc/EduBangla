<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\FinancialAdjustment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostFinancialAdjustment
{
    public function handle(User $actor, int $schoolId, array $data, ?\Closure $afterCreate = null): FinancialAdjustment
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $data, $afterCreate) {
            $invoice = Invoice::where(['school_id' => $schoolId, 'id' => $data['invoice_id']])->lockForUpdate()->firstOrFail();
            if ($invoice->status === 'void') {
                throw ValidationException::withMessages(['invoice' => 'Void invoices cannot be adjusted.']);
            }
            $amount = round((float) $data['amount'], 2);
            $due = (float) app(FinanceBalance::class)->calculate($invoice, $schoolId)['outstanding_total'];
            if ($amount <= 0 || $amount > $due) {
                throw ValidationException::withMessages(['amount' => 'Adjustment must be positive and no larger than the outstanding due.']);
            }
            $adjustment = FinancialAdjustment::create(['school_id' => $schoolId, 'invoice_id' => $invoice->id, 'kind' => 'credit', 'reason' => trim($data['reason']), 'amount' => $amount, 'status' => 'posted', 'posted_by' => $actor->id, 'posted_at' => now(), 'snapshot' => ['invoice_number' => $invoice->invoice_number, 'due_before' => number_format($due, 2, '.', '')]]);
            if ($afterCreate) {
                $afterCreate($adjustment);
            }
            app(FinanceBalance::class)->refresh($invoice, $schoolId);
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.adjustment_posted', $adjustment, null, ['invoice_id' => $invoice->id, 'amount' => (string) $adjustment->amount, 'reason' => $adjustment->reason]);

            return $adjustment->refresh();
        });
    }
}

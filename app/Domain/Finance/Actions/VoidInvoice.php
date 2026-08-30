<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidInvoice
{
    public function handle(User $actor, int $schoolId, int $invoiceId, ?\Closure $afterMutation = null): Invoice
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $invoiceId, $afterMutation) {
            $invoice = Invoice::where(['school_id' => $schoolId, 'id' => $invoiceId])->lockForUpdate()->firstOrFail();
            if (! in_array($invoice->status, ['issued'], true) || $invoice->allocations()->whereHas('payment', fn ($q) => $q->whereIn('status', ['recorded', 'allocated']))->exists()) {
                throw ValidationException::withMessages(['invoice' => 'Only an untouched issued invoice can be voided.']);
            }
            $before = $invoice->only(['status', 'outstanding_total']);
            $invoice->update(['status' => 'void', 'voided_at' => now(), 'outstanding_total' => '0.00']);
            if ($afterMutation) {
                $afterMutation($invoice);
            }
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.invoice_voided', $invoice, $before, $invoice->only(['status', 'outstanding_total']));

            return $invoice->refresh();
        });
    }
}

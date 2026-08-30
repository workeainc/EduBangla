<?php

namespace App\Domain\Finance;

use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class FinanceBalance
{
    public function calculate(Invoice $invoice, int $schoolId): array
    {
        if ($invoice->school_id !== $schoolId) {
            throw ValidationException::withMessages(['invoice' => 'Invalid invoice scope.']);
        }

        $charged = (float) $invoice->items()->sum('amount');
        $allocated = (float) $invoice->allocations()->whereHas('payment', fn ($q) => $q->whereIn('status', ['recorded', 'allocated']))->sum('amount');
        $adjusted = (float) $invoice->adjustments()->where(['kind' => 'credit', 'status' => 'posted'])->sum('amount');
        $outstanding = round($charged - $allocated - $adjusted, 2);
        if ($outstanding < 0) {
            throw ValidationException::withMessages(['invoice' => 'Financial facts exceed the invoice charge.']);
        }

        return ['charged_total' => number_format($charged, 2, '.', ''), 'allocated_total' => number_format($allocated, 2, '.', ''), 'adjustment_total' => number_format($adjusted, 2, '.', ''), 'outstanding_total' => number_format($outstanding, 2, '.', '')];
    }

    public function refresh(Invoice $invoice, int $schoolId): Invoice
    {
        $totals = $this->calculate($invoice->refresh(), $schoolId);
        $invoice->update($totals + ['status' => $totals['outstanding_total'] === '0.00' ? 'paid' : ($totals['allocated_total'] === '0.00' ? 'issued' : 'partially_paid')]);

        return $invoice->refresh();
    }
}

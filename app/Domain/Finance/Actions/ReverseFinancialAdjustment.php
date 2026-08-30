<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Domain\Finance\FinanceBalance;
use App\Models\FinancialAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseFinancialAdjustment
{
    public function handle(User $actor, int $schoolId, int $adjustmentId, ?\Closure $afterMutation = null): FinancialAdjustment
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $adjustmentId, $afterMutation) {
            $adjustment = FinancialAdjustment::where(['school_id' => $schoolId, 'id' => $adjustmentId])->lockForUpdate()->firstOrFail();
            if ($adjustment->status !== 'posted' || $adjustment->reversal_of_adjustment_id) {
                throw ValidationException::withMessages(['adjustment' => 'Only a posted adjustment can be reversed.']);
            }
            $before = $adjustment->only(['status']);
            $adjustment->update(['status' => 'reversed', 'reversed_at' => now()]);
            $reversal = FinancialAdjustment::create(['school_id' => $schoolId, 'invoice_id' => $adjustment->invoice_id, 'kind' => 'reversal', 'reason' => 'Reversal of adjustment #'.$adjustment->id, 'amount' => $adjustment->amount, 'status' => 'posted', 'posted_by' => $actor->id, 'posted_at' => now(), 'reversal_of_adjustment_id' => $adjustment->id, 'snapshot' => ['original_id' => $adjustment->id, 'amount' => (string) $adjustment->amount]]);
            if ($afterMutation) {
                $afterMutation($adjustment, $reversal);
            }
            app(FinanceBalance::class)->refresh($adjustment->invoice, $schoolId);
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.adjustment_reversed', $adjustment, $before, ['status' => 'reversed', 'reversal_id' => $reversal->id]);

            return $reversal->refresh();
        });
    }
}

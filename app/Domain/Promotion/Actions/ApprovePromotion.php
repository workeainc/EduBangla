<?php

namespace App\Domain\Promotion\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovePromotion
{
    public function handle(Promotion $p, int $schoolId): Promotion
    {
        return DB::transaction(function () use ($p, $schoolId) {
            if ($p->school_id !== $schoolId || $p->status !== 'eligible' || $p->decision !== 'eligible') {
                throw ValidationException::withMessages(['promotion' => 'Only eligible promotions can be approved.']);
            }$p->update(['status' => 'approved', 'decided_by' => auth()->id(), 'decided_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'promotion.approved', $p);
            }

return $p->refresh();
        });
    }
}

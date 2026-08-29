<?php

namespace App\Domain\Promotion\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPromotion
{
    public function handle(Promotion $p, int $schoolId): Promotion
    {
        return DB::transaction(function () use ($p, $schoolId) {
            if ($p->school_id !== $schoolId || in_array($p->status, ['applied', 'cancelled'], true)) {
                throw ValidationException::withMessages(['promotion' => 'Promotion cannot be cancelled now.']);
            }$p->update(['status' => 'cancelled', 'decision' => 'cancelled', 'decided_by' => auth()->id(), 'decided_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'promotion.cancelled', $p);
            }

            return $p->refresh();
        });
    }
}

<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Finance\FinanceAuthorizer;
use App\Models\FeeStructure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateFeeStructure
{
    public function handle(User $actor, int $schoolId, int $structureId): FeeStructure
    {
        FinanceAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $structureId) {
            $structure = FeeStructure::where(['school_id' => $schoolId, 'id' => $structureId])->lockForUpdate()->firstOrFail();
            if ($structure->status !== 'draft' || ! $structure->items()->exists()) {
                throw ValidationException::withMessages(['structure' => 'Only a non-empty draft can be activated.']);
            }
            $before = $structure->only(['status']);
            $structure->update(['status' => 'active']);
            app(RecordAudit::class)->handle($actor, $schoolId, 'finance.fee_structure_activated', $structure, $before, $structure->only(['status']));

            return $structure->refresh();
        });
    }
}

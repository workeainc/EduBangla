<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LockResult
{
    public function handle(Result $result, int $schoolId): Result
    {
        return DB::transaction(function () use ($result, $schoolId) {
            if ($result->school_id !== $schoolId || $result->status !== 'computed') {
                throw ValidationException::withMessages(['result' => 'শুধু computed result lock করা যায়।']);
            }$result->update(['status' => 'locked']);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'result.locked', $result);
            }

return $result->refresh();
        });
    }
}

<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishResult
{
    public function handle(Result $result, int $schoolId): Result
    {
        return DB::transaction(function () use ($result, $schoolId) {
            if ($result->school_id !== $schoolId || $result->status !== 'locked') {
                throw ValidationException::withMessages(['result' => 'শুধু locked result publish করা যায়।']);
            }$result->update(['status' => 'published', 'published_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'result.published', $result);
            }

return $result->refresh();
        });
    }
}

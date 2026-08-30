<?php

namespace App\Domain\Communication\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Communication\CommunicationAuthorizer;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawNotice
{
    public function handle(User $actor, int $schoolId, int $noticeId): Notice
    {
        CommunicationAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $noticeId) {
            $notice = Notice::forSchool($schoolId)->lockForUpdate()->findOrFail($noticeId);
            if ($notice->status !== 'published') {
                throw ValidationException::withMessages(['notice' => 'Only published notices can be withdrawn.']);
            }
            $notice->update(['status' => 'withdrawn', 'withdrawn_at' => now(), 'updated_by' => $actor->id]);
            app(RecordAudit::class)->handle($actor, $schoolId, 'communication.notice_withdrawn', $notice, ['status' => 'published'], ['status' => 'withdrawn']);

            return $notice->refresh()->load('deliveries');
        });
    }
}

<?php

namespace App\Domain\Communication\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Communication\CommunicationAuthorizer;
use App\Domain\Communication\RecipientResolver;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveNoticeDraft
{
    public function handle(User $actor, int $schoolId, array $data, ?int $noticeId = null): Notice
    {
        CommunicationAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $data, $noticeId) {
            $audiences = app(RecipientResolver::class)->normalize($schoolId, $data['audiences'] ?? []);
            $notice = $noticeId ? Notice::forSchool($schoolId)->lockForUpdate()->findOrFail($noticeId) : null;
            if ($notice && $notice->status !== 'draft') {
                throw ValidationException::withMessages(['notice' => 'Only draft notices can be updated.']);
            }
            $payload = ['title' => trim((string) ($data['title'] ?? '')), 'body' => trim((string) ($data['body'] ?? '')), 'updated_by' => $actor->id];
            if ($payload['title'] === '' || $payload['body'] === '') {
                throw ValidationException::withMessages(['notice' => 'Title and body are required.']);
            }
            if (! $notice) {
                $notice = Notice::create($payload + ['school_id' => $schoolId, 'created_by' => $actor->id, 'status' => 'draft']);
            } else {
                $notice->update($payload);
                $notice->audiences()->delete();
            }
            foreach ($audiences as $audience) {
                $notice->audiences()->create($audience + ['school_id' => $schoolId]);
            }
            app(RecordAudit::class)->handle($actor, $schoolId, $notice->wasRecentlyCreated ? 'communication.notice_created' : 'communication.notice_updated', $notice, null, ['status' => 'draft', 'audience_count' => count($audiences)]);

            return $notice->refresh()->load('audiences');
        });
    }
}

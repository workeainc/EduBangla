<?php

namespace App\Domain\Communication\Actions;

use App\Domain\Audit\RecordAudit;
use App\Domain\Communication\CommunicationAuthorizer;
use App\Domain\Communication\RecipientResolver;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishNotice
{
    public function handle(User $actor, int $schoolId, int $noticeId, ?\Closure $afterFirstDelivery = null): Notice
    {
        CommunicationAuthorizer::admin($actor, $schoolId);

        return DB::transaction(function () use ($actor, $schoolId, $noticeId, $afterFirstDelivery) {
            $notice = Notice::forSchool($schoolId)->lockForUpdate()->findOrFail($noticeId);
            if ($notice->status !== 'draft') {
                throw ValidationException::withMessages(['notice' => 'Only draft notices can be published.']);
            }
            $audiences = $notice->audiences()->where('school_id', $schoolId)->lockForUpdate()->get();
            $recipients = app(RecipientResolver::class)->recipients($schoolId, $audiences);
            if ($recipients === []) {
                throw ValidationException::withMessages(['audiences' => 'The selected audience has no active recipients.']);
            }
            $publishedAt = now();
            foreach ($audiences as $audience) {
                $audience->update(['published_at' => $publishedAt, 'snapshot' => $audience->only(['type', 'role', 'academic_year_id', 'class_id', 'section_id'])]);
            }
            foreach ($recipients as $offset => $recipient) {
                $notice->deliveries()->create($recipient + ['school_id' => $schoolId, 'delivered_at' => $publishedAt]);
                if ($offset === 0 && $afterFirstDelivery) {
                    $afterFirstDelivery();
                }
            }
            $notice->update(['status' => 'published', 'published_at' => $publishedAt, 'updated_by' => $actor->id]);
            app(RecordAudit::class)->handle($actor, $schoolId, 'communication.notice_published', $notice, ['status' => 'draft'], ['status' => 'published', 'recipient_count' => count($recipients)]);

            return $notice->refresh()->load(['audiences', 'deliveries']);
        });
    }
}

<?php

namespace App\Domain\Result\Actions;

use App\Domain\Audit\RecordAudit;
use App\Models\ReportCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishReportCard
{
    public function handle(ReportCard $card, int $schoolId): ReportCard
    {
        return DB::transaction(function () use ($card, $schoolId) {
            if ($card->school_id !== $schoolId || $card->status !== 'generated') {
                throw ValidationException::withMessages(['report' => 'Only generated report cards can be published.']);
            }$card->update(['status' => 'published', 'published_at' => now()]);
            if (auth()->user()) {
                app(RecordAudit::class)->handle(auth()->user(), $schoolId, 'report_card.published', $card);
            }

            return $card->refresh();
        });
    }
}

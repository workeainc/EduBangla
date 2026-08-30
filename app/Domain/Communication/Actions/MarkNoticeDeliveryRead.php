<?php

namespace App\Domain\Communication\Actions;

use App\Domain\Communication\CommunicationAuthorizer;
use App\Models\NoticeDelivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkNoticeDeliveryRead
{
    public function handle(User $actor, int $schoolId, string $role, int $deliveryId): NoticeDelivery
    {
        CommunicationAuthorizer::recipient($actor, $schoolId, $role);

        return DB::transaction(function () use ($actor, $schoolId, $deliveryId) {
            $delivery = NoticeDelivery::forSchool($schoolId)->where('user_id', $actor->id)->lockForUpdate()->findOrFail($deliveryId);
            if ($delivery->read_at === null) {
                $delivery->update(['read_at' => now()]);
            }

            return $delivery->refresh()->load('notice');
        });
    }
}

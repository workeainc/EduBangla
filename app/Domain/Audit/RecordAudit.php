<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordAudit
{
    public function handle(User $actor, int $schoolId, string $action, Model $target, ?array $before = null, ?array $after = null): AuditLog
    {
        return AuditLog::create(['school_id' => $schoolId, 'actor_id' => $actor->id, 'action' => $action, 'auditable_type' => $target::class, 'auditable_id' => $target->getKey(), 'before' => $before, 'after' => $after]);
    }
}

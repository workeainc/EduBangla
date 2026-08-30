<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class NoticeDelivery extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'notice_id', 'user_id', 'recipient_role', 'profile_type', 'profile_id', 'recipient_snapshot', 'delivered_at', 'read_at'];

    protected function casts(): array
    {
        return ['recipient_snapshot' => 'array', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $delivery) {
            $immutableChanges = array_diff_key($delivery->getDirty(), array_flip(['read_at', 'updated_at']));
            if ($immutableChanges !== []) {
                throw new \RuntimeException('Notice delivery facts are immutable.');
            }
        });

        static::deleting(fn () => throw new \RuntimeException('Notice deliveries cannot be deleted.'));
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'title', 'body', 'status', 'created_by', 'updated_by', 'published_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function audiences()
    {
        return $this->hasMany(NoticeAudience::class);
    }

    public function deliveries()
    {
        return $this->hasMany(NoticeDelivery::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $notice) {
            if ($notice->getOriginal('status') !== 'draft' && $notice->isDirty(['title', 'body', 'created_by', 'school_id', 'published_at'])) {
                throw new \RuntimeException('Published notices are immutable.');
            }
        });

        static::deleting(function (self $notice) {
            if ($notice->status !== 'draft') {
                throw new \RuntimeException('Published notices cannot be deleted.');
            }
        });
    }
}

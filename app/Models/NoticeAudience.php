<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class NoticeAudience extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'notice_id', 'type', 'role', 'academic_year_id', 'class_id', 'section_id', 'snapshot', 'published_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'published_at' => 'datetime'];
    }

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $audience) {
            if ($audience->notice()->value('status') !== 'draft') {
                throw new \RuntimeException('Published notice audiences are immutable.');
            }
        });

        static::deleting(function (self $audience) {
            if ($audience->notice()->value('status') !== 'draft') {
                throw new \RuntimeException('Published notice audiences cannot be deleted.');
            }
        });
    }
}

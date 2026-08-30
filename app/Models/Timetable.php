<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'academic_year_id', 'class_id', 'section_id', 'name', 'status', 'created_by', 'updated_by', 'published_at', 'archived_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function slots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $timetable) {
            if ($timetable->getOriginal('status') !== 'draft' && $timetable->isDirty(['school_id', 'academic_year_id', 'class_id', 'section_id', 'name', 'created_by', 'published_at'])) {
                throw new \RuntimeException('Published timetables are immutable.');
            }
        });
        static::deleting(function (self $timetable) {
            if ($timetable->status !== 'draft') {
                throw new \RuntimeException('Published timetables cannot be deleted.');
            }
        });
    }
}

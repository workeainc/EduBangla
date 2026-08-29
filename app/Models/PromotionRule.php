<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'source_class_id', 'target_class_id', 'minimum_overall_status', 'minimum_gpa', 'minimum_passed_subjects', 'failed_subject_tolerance', 'active'];

    protected $casts = ['minimum_gpa' => 'decimal:2', 'active' => 'boolean'];

    public function sourceClass()
    {
        return $this->belongsTo(AcademicClass::class, 'source_class_id');
    }

    public function targetClass()
    {
        return $this->belongsTo(AcademicClass::class, 'target_class_id');
    }
}

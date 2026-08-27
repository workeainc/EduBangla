<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'class_id', 'name', 'code', 'capacity', 'status'];

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\AcademicGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicGroup extends Model
{
    /** @use HasFactory<AcademicGroupFactory> */
    use BelongsToSchool, HasFactory;

    protected $table = 'groups';

    protected $fillable = ['school_id', 'name', 'code', 'status'];
}

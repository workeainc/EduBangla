<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\AcademicClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicClass extends Model
{
    /** @use HasFactory<AcademicClassFactory> */
    use BelongsToSchool, HasFactory;

    protected $table = 'classes';

    protected $fillable = ['school_id', 'name', 'code', 'sort_order', 'status'];
}

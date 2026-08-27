<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use BelongsToSchool;

    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'user_id', 'employee_code', 'first_name', 'last_name', 'joining_date', 'status'];
}

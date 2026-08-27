<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use BelongsToSchool;

    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'user_id', 'employee_code', 'name', 'designation', 'joining_date', 'status'];
}

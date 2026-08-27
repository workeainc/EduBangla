<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'name', 'phone', 'email', 'address', 'status'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_guardians')->withPivot(['relationship_type', 'is_primary'])->withTimestamps();
    }
}

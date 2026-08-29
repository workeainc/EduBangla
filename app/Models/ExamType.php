<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamType extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'name', 'code', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}

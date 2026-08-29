<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'subject_id', 'name', 'language', 'curriculum_version', 'status'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

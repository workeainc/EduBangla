<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'question_version_id', 'option_key', 'option_text', 'is_correct'];

    protected $casts = ['is_correct' => 'boolean'];
}

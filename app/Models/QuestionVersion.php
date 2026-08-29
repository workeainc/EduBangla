<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'question_id', 'version', 'prompt', 'marks', 'language', 'answer_config', 'created_by'];

    protected $casts = ['answer_config' => 'array'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}

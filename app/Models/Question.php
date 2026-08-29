<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use BelongsToSchool,HasFactory;

    protected $fillable = ['school_id', 'question_bank_id', 'stable_key', 'type', 'topic', 'learning_objective', 'difficulty', 'status'];

    public function bank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function versions()
    {
        return $this->hasMany(QuestionVersion::class);
    }
}

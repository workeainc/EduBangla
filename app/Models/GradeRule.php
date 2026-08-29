<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeRule extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'name', 'minimum_percentage', 'maximum_percentage', 'letter_grade', 'grade_point', 'is_pass', 'sort_order', 'active'];

    protected $casts = ['minimum_percentage' => 'decimal:2', 'maximum_percentage' => 'decimal:2', 'grade_point' => 'decimal:2', 'is_pass' => 'boolean', 'active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(ResultItem::class);
    }
}

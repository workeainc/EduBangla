<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'code', 'name', 'description', 'status'];

    public function structureItems()
    {
        return $this->hasMany(FeeStructureItem::class);
    }
}

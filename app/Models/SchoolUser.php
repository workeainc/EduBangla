<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolUser extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'school_users';

    protected $fillable = ['school_id', 'user_id', 'role', 'status'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

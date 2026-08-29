<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'slug', 'email', 'phone', 'address', 'status'];

    public function memberships()
    {
        return $this->hasMany(SchoolUser::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function hasActiveMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->where('status', SchoolUser::STATUS_ACTIVE)->exists();
    }
}

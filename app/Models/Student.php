<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'user_id', 'student_code', 'first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'email', 'address', 'admission_date', 'status'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'admission_date' => 'date'];
    }

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')->withPivot(['relationship_type', 'is_primary'])->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

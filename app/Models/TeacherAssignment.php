<?php

namespace App\Models;

use Database\Factories\TeacherAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    /** @use HasFactory<TeacherAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'teacher_id', 'academic_year_id', 'class_id', 'section_id', 'subject_assignment_id', 'group_id', 'group_scope'];
}

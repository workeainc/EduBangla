<?php

namespace App\Models;

use Database\Factories\SubjectAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectAssignment extends Model
{
    /** @use HasFactory<SubjectAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'academic_year_id', 'class_id', 'subject_id', 'group_id', 'group_scope'];
}

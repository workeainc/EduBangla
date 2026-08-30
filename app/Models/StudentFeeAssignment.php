<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentFeeAssignment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'enrollment_id', 'academic_year_id', 'class_id', 'section_id', 'fee_structure_id', 'fee_structure_item_id', 'fee_category_id', 'category_code', 'category_name', 'amount', 'due_date', 'snapshot', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date', 'snapshot' => 'array'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function structure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function structureItem()
    {
        return $this->belongsTo(FeeStructureItem::class, 'fee_structure_item_id');
    }

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }
}

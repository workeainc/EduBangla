<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'enrollment_id', 'academic_year_id', 'class_id', 'section_id', 'invoice_number', 'currency', 'status', 'issued_at', 'due_date', 'charged_total', 'allocated_total', 'adjustment_total', 'outstanding_total', 'student_snapshot', 'enrollment_snapshot', 'voided_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'due_date' => 'date', 'voided_at' => 'datetime', 'charged_total' => 'decimal:2', 'allocated_total' => 'decimal:2', 'adjustment_total' => 'decimal:2', 'outstanding_total' => 'decimal:2', 'student_snapshot' => 'array', 'enrollment_snapshot' => 'array'];
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function adjustments()
    {
        return $this->hasMany(FinancialAdjustment::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $invoice) {
            if (in_array($invoice->getOriginal('status'), ['issued', 'partially_paid', 'paid', 'void'], true) && $invoice->isDirty(['student_id', 'enrollment_id', 'academic_year_id', 'class_id', 'section_id', 'invoice_number', 'currency', 'issued_at', 'due_date', 'student_snapshot', 'enrollment_snapshot'])) {
                throw new \RuntimeException('Issued invoices are immutable.');
            }
        });
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'invoice_id', 'student_fee_assignment_id', 'fee_category_id', 'category_code', 'category_name', 'amount', 'due_date'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function assignment()
    {
        return $this->belongsTo(StudentFeeAssignment::class, 'student_fee_assignment_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Invoice items are immutable.'));
        static::deleting(fn () => throw new \RuntimeException('Invoice items are immutable.'));
    }
}

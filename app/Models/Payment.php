<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'enrollment_id', 'receipt_number', 'currency', 'amount', 'received_at', 'method', 'reference', 'recorded_by', 'status', 'reversal_of_payment_id', 'reversed_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'received_at' => 'date', 'reversed_at' => 'datetime'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_payment_id');
    }

    protected static function booted(): void
    {
        static::updating(function (self $payment) {
            if (in_array($payment->getOriginal('status'), ['recorded', 'allocated', 'reversed'], true) && $payment->isDirty(['student_id', 'enrollment_id', 'receipt_number', 'currency', 'amount', 'received_at', 'method', 'reference', 'recorded_by', 'reversal_of_payment_id'])) {
                throw new \RuntimeException('Recorded payments are immutable.');
            }
        });
    }
}

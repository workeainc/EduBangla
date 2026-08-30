<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'payment_id', 'invoice_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \RuntimeException('Payment allocations are immutable.'));
        static::deleting(fn () => throw new \RuntimeException('Payment allocations are immutable.'));
    }
}

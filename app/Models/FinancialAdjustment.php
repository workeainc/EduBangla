<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FinancialAdjustment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'invoice_id', 'kind', 'reason', 'amount', 'status', 'posted_by', 'posted_at', 'reversal_of_adjustment_id', 'reversed_at', 'snapshot'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'posted_at' => 'datetime', 'reversed_at' => 'datetime', 'snapshot' => 'array'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_adjustment_id');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    protected static function booted(): void
    {
        static::updating(function (self $adjustment) {
            if (in_array($adjustment->getOriginal('status'), ['posted', 'reversed'], true) && $adjustment->isDirty(['invoice_id', 'kind', 'reason', 'amount', 'posted_by', 'posted_at', 'reversal_of_adjustment_id', 'snapshot'])) {
                throw new \RuntimeException('Posted adjustments are immutable.');
            }
        });
    }
}

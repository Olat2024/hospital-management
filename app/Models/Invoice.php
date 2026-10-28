<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUSES = ['unpaid', 'partial', 'paid', 'void'];

    public const PAYMENT_METHODS = ['cash', 'card', 'transfer', 'insurance'];

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'invoice_number',
        'status',
        'subtotal',
        'discount',
        'total',
        'amount_paid',
        'notes',
        'issued_at',
        'due_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected $appends = ['balance'];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at');
    }

    /**
     * Remaining balance as a number (casts yield numeric strings).
     */
    public function getBalanceAttribute(): float
    {
        return (float) round($this->total - $this->amount_paid, 2);
    }

    /**
     * Recompute status from how much has been paid. Void is sticky.
     */
    public function refreshStatus(): self
    {
        if ($this->status !== 'void') {
            $paid = (float) $this->amount_paid;
            $total = (float) $this->total;

            $this->status = match (true) {
                $paid <= 0 => 'unpaid',
                $paid + 0.001 >= $total => 'paid',
                default => 'partial',
            };
        }

        return $this;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockMovement extends Model
{
    protected $fillable = [
        'medicine_id',
        'recorded_by',
        'dispensation_id',
        'type',
        'quantity',
        'reference',
        'recorded_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function dispensation(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensation::class);
    }
}

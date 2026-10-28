<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyDispensationItem extends Model
{
    protected $fillable = [
        'dispensation_id',
        'medicine_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function dispensation(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensation::class, 'dispensation_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}

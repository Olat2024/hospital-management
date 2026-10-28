<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    protected $fillable = [
        'hospital_id',
        'name',
        'generic_name',
        'strength',
        'dosage_form',
        'stock_quantity',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'reorder_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(PharmacyStockMovement::class);
    }

    public function dispensationItems(): HasMany
    {
        return $this->hasMany(PharmacyDispensationItem::class);
    }
}

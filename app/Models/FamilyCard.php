<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyCard extends Model
{
    protected $fillable = [
        'hospital_id',
        'card_number',
        'family_name',
        'contact_name',
        'phone',
        'address',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}

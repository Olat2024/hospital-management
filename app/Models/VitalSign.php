<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    protected $fillable = [
        'recorded_by',
        'systolic_blood_pressure',
        'diastolic_blood_pressure',
        'pulse_rate',
        'respiratory_rate',
        'temperature_celsius',
        'oxygen_saturation',
        'weight_kg',
        'height_cm',
        'pain_score',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'temperature_celsius' => 'decimal:1',
        'weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

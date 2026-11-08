<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AntenatalCard extends Model
{
    protected $fillable = [
        'hospital_id',
        'patient_id',
        'created_by',
        'booking_date',
        'pregnancy_duration_at_booking',
        'age_at_booking',
        'age_at_marriage',
        'last_menstrual_period',
        'expected_delivery_date',
        'tribe',
        'occupation',
        'special_points',
        'previous_medical_history',
        'previous_pregnancies',
        'previous_pregnancies_total',
        'living_children',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'last_menstrual_period' => 'date',
        'expected_delivery_date' => 'date',
        'previous_medical_history' => 'array',
        'previous_pregnancies' => 'array',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(AntenatalVisit::class)->orderBy('visit_date');
    }
}

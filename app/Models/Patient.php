<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'hospital_id',
        'family_card_id',
        'family_relationship',
        'mrn',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'blood_group',
        'phone',
        'email',
        'address',
        'allergies',
        'emergency_contact_name',
        'emergency_contact_phone',
        'insurance_provider',
        'insurance_number',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    protected $appends = [
        'dob_display',
    ];

    public function getDobDisplayAttribute(): ?string
    {
        return $this->dob?->format('M j, Y');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function familyCard(): BelongsTo
    {
        return $this->belongsTo(FamilyCard::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class)->latest('recorded_at');
    }

    public function latestVitalSign(): HasOne
    {
        return $this->hasOne(VitalSign::class)->latestOfMany('recorded_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class)->latest('admitted_at');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class)->latest('referred_at');
    }
}

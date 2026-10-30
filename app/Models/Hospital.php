<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Hospital extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'tagline',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'social_links',
        'footer_text',
        'is_active',
        'timezone',
        'currency',
        'locale',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'social_links' => 'array',
    ];

    public static function default(): self
    {
        $hospital = static::query()->where('is_active', true)->first()
            ?? static::query()->first();

        if ($hospital) {
            return $hospital;
        }

        return static::query()->create([
            'name' => 'Siloam Grace Clinic & Maternity Home',
            'is_active' => true,
        ]);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function setup(): HasOne
    {
        return $this->hasOne(HospitalSetup::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo
            ? Storage::disk('public')->url($this->logo)
            : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon
            ? Storage::disk('public')->url($this->favicon)
            : null;
    }
}

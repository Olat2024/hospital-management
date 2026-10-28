<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HospitalSetup extends Model
{
    protected $fillable = [
        'hospital_id',
        'logo', 'favicon',
        'primary_color', 'secondary_color',
        'name', 'tagline',
        'address', 'city', 'state', 'country', 'postal_code',
        'phone', 'email', 'website',
        'social_links', 'footer_text',
        'timezone', 'currency', 'locale',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    protected $appends = ['logo_url', 'favicon_url'];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public static function forHospital(int $hospitalId): self
    {
        return static::firstOrCreate(
            ['hospital_id' => $hospitalId],
            ['primary_color' => '#0d6efd', 'secondary_color' => '#6c757d']
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : null;
    }
}
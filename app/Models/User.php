<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    /**
     * Public URL of the user's uploaded photo, or null when none is set.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar
            ? Storage::disk('public')->url($this->avatar)
            : null;
    }

    public function paymentsRecorded(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function pharmacyDispensationsRecorded(): HasMany
    {
        return $this->hasMany(PharmacyDispensation::class, 'dispensed_by');
    }

    public function pharmacyStockMovementsRecorded(): HasMany
    {
        return $this->hasMany(PharmacyStockMovement::class, 'recorded_by');
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}

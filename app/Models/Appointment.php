<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'hospital_id',
        'patient_id',
        'doctor_id',
        'scheduled_at',
        'status',
        'type',
        'reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    protected $appends = [
        'scheduled_date',
        'scheduled_time',
    ];

    /**
     * Human friendly date/time for the Inertia front-end.
     */
    public function getScheduledDateAttribute(): ?string
    {
        return $this->scheduled_at?->format('D, M j, Y');
    }

    public function getScheduledTimeAttribute(): ?string
    {
        return $this->scheduled_at?->format('g:i A');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}

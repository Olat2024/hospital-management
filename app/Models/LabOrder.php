<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabOrder extends Model
{
    public const PRIORITIES = ['routine', 'urgent', 'stat'];

    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'ordered_by',
        'processed_by',
        'test_name',
        'category',
        'priority',
        'status',
        'notes',
        'result_summary',
        'result_notes',
        'ordered_at',
        'completed_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

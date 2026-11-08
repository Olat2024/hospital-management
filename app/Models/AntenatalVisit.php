<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntenatalVisit extends Model
{
    protected $fillable = [
        'visit_date',
        'fundal_height_cm',
        'presentation_position',
        'presenting_part_relation',
        'foetal_heart',
        'urine',
        'blood_pressure',
        'weight_kg',
        'pcv',
        'oedema',
        'remarks',
        'examiner_initials',
        'return_date',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'return_date' => 'date',
    ];

    public function antenatalCard(): BelongsTo
    {
        return $this->belongsTo(AntenatalCard::class);
    }
}

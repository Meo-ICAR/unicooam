<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unico\Core\Models\TrainingRecord as CoreTrainingRecord;

class TrainingRecord extends CoreTrainingRecord
{
    use HasFactory, SoftDeletes;
    protected $orderBy = 'expiry_date';
    protected $orderDirection = 'asc';

    protected $casts = [
        'training_date' => 'date',
        'expiry_date' => 'date',
        'hours' => 'decimal:1',
        'score' => 'decimal:2',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Relazione polimorfica: recupera il modello associato al corso
     * (es. puo' essere un User, un Employee, un Consulente, ecc.)
     */
    public function trainable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relazione con l'Azienda
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
